<?php

namespace App\Services\Billing;

use App\Jobs\SendTenantPaymentInvoice;
use App\Models\Coupon;
use App\Models\StripeCheckoutAttempt;
use App\Models\StripeWebhookEvent;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Services\Audit\AuditLogger;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class StripeWebhookService
{
    public function __construct(protected StripeGateway $gateway, protected AuditLogger $audit) {}

    public function handle(array $event): void
    {
        $type = $event['type'];
        if (StripeWebhookEvent::whereKey($event['id'])->exists()) {
            return;
        }
        $handled = ['checkout.session.completed', 'checkout.session.expired', 'invoice.paid',
            'invoice.payment_failed', 'customer.subscription.updated', 'customer.subscription.deleted'];
        if (! in_array($type, $handled, true)) {
            $this->recordEvent($event);
            return;
        }

        $object = $event['data']['object'];
        $invoice = null;
        if (str_starts_with($type, 'invoice.')) {
            $invoice = $this->gateway->invoice($object['id']);
            $subscriptionId = $this->id(data_get($invoice, 'parent.subscription_details.subscription') ?? ($invoice['subscription'] ?? null));
            if (! $subscriptionId) {
                $this->recordEvent($event);
                return;
            }
            $subscription = $this->gateway->subscription($subscriptionId);
            $attemptId = data_get($subscription, 'metadata.checkout_attempt_id');
        } elseif (str_starts_with($type, 'customer.subscription.')) {
            $subscriptionId = $object['id'];
            $attemptId = data_get($object, 'metadata.checkout_attempt_id');
        } else {
            $subscriptionId = $this->id($object['subscription'] ?? null);
            $attemptId = data_get($object, 'metadata.checkout_attempt_id');
        }

        $attempt = $attemptId ? StripeCheckoutAttempt::withoutGlobalScopes()->find($attemptId) : null;
        if (! $attempt) {
            // Events belonging to another Stripe integration have no local entitlement effect.
            $this->recordEvent($event);
            return;
        }

        DB::transaction(function () use ($event, $type, $object, $invoice, $subscriptionId, $attempt): void {
            $tenant = Tenant::whereKey($attempt->tenant_id)->lockForUpdate()->firstOrFail();
            if (StripeWebhookEvent::whereKey($event['id'])->exists()) {
                return;
            }
            $attempt = StripeCheckoutAttempt::withoutGlobalScopes()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($type === 'checkout.session.expired') {
                $this->assertSession($object, $attempt, $tenant);
                if (in_array($attempt->status, ['creating', 'open'], true)) {
                    $attempt->update(['status' => 'expired', 'stripe_session_id' => $object['id']]);
                }
                $this->recordEvent($event);
                return;
            }
            if (! $subscriptionId) {
                throw new DomainException('Checkout has no subscription identity.');
            }

            // Fetch after acquiring the tenant lock: competing event processors cannot apply an older read.
            $subscription = $this->gateway->subscription($subscriptionId);
            $this->assertSubscription($subscription, $attempt, $tenant);
            if ($tenant->stripe_subscription_id && $tenant->stripe_subscription_id !== $subscriptionId) {
                if (! in_array($tenant->stripe_status, ['canceled', 'incomplete_expired'], true)
                    || ! in_array($attempt->status, ['creating', 'open', 'awaiting_payment'], true)) {
                    $this->recordEvent($event);
                    return;
                }
            }

            if ($type === 'checkout.session.completed') {
                $this->assertSession($object, $attempt, $tenant);
                $attempt->update(['stripe_session_id' => $object['id'],
                    'status' => $attempt->status === 'completed' ? 'completed' : 'awaiting_payment']);
            }

            $payment = null;
            if ($invoice) {
                if ($this->id($invoice['customer'] ?? null) !== $tenant->stripe_customer_id) {
                    throw new DomainException('Invoice customer does not match the tenant.');
                }
                if ($type === 'invoice.paid' && ($invoice['status'] ?? null) === 'paid') {
                    $payment = $this->recordInvoice($invoice, $subscriptionId, $attempt, $tenant);
                }
            }

            $hasPaid = TenantPayment::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->where('stripe_subscription_id', $subscriptionId)->where('status', 'completed')->exists();
            $status = $subscription['status'];
            $updates = ['stripe_subscription_id' => $subscriptionId, 'stripe_status' => $status,
                'stripe_cancel_at_period_end' => (bool) ($subscription['cancel_at_period_end'] ?? false) || ! empty($subscription['cancel_at'])];
            if ($hasPaid) {
                $updates['subscription_status'] = match ($status) {
                    'active', 'trialing' => 'active',
                    'canceled', 'incomplete_expired' => 'canceled',
                    'paused' => 'paused',
                    default => 'past_due',
                };
                $updates['billing_provider'] = 'stripe';
                $updates['billing_customer_id'] = $tenant->stripe_customer_id;
                $updates['billing_subscription_id'] = $subscriptionId;
            }
            $tenant->update($updates);
            $this->recordEvent($event);
            $this->audit->log('stripe_subscription_synced', Tenant::class, $tenant->id,
                ['tenant_id' => $tenant->id, 'source' => 'stripe', 'after' => ['status' => $status, 'event_id' => $event['id']]]);
            if ($payment) {
                SendTenantPaymentInvoice::dispatch($payment->id)->afterCommit();
            }
        });
    }

    protected function recordInvoice(array $invoice, string $subscriptionId, StripeCheckoutAttempt $attempt, Tenant $tenant): ?TenantPayment
    {
        if (TenantPayment::withoutGlobalScopes()->where('stripe_invoice_id', $invoice['id'])->exists()) {
            return null;
        }
        $initial = ($invoice['billing_reason'] ?? null) === 'subscription_create';
        $expected = (int) round((float) ($initial ? $attempt->total_amount : $attempt->base_amount) * 100);
        if (strtolower($invoice['currency'] ?? '') !== 'usd' || (int) ($invoice['total'] ?? -1) !== $expected) {
            throw new DomainException('Invoice currency or total differs from the agreed subscription.');
        }
        $lines = data_get($invoice, 'lines.data', []);
        if (count($lines) !== 1 || ! is_numeric(data_get($lines, '0.period.end'))) {
            throw new DomainException('Invoice must contain one recurring paid period.');
        }
        $end = Carbon::createFromTimestampUTC((int) data_get($lines, '0.period.end'));
        $discount = array_sum(array_column($invoice['total_discount_amounts'] ?? [], 'amount')) / 100;
        $payment = TenantPayment::create([
            'tenant_id' => $tenant->id, 'package_id' => $attempt->package_id,
            'coupon_id' => $initial ? $attempt->coupon_id : null,
            'payment_gateway' => 'stripe', 'gateway_transaction_id' => $invoice['id'],
            'stripe_invoice_id' => $invoice['id'], 'stripe_subscription_id' => $subscriptionId,
            'stripe_checkout_attempt_id' => $attempt->id, 'amount' => ((int) $invoice['amount_paid']) / 100,
            'currency' => 'USD', 'billing_cycle' => $attempt->billing_cycle, 'status' => 'completed',
            'metadata' => ['base_price' => ((int) $invoice['subtotal']) / 100, 'discount' => $discount,
                'package_name' => $attempt->metadata['package_name'], 'max_employees' => $attempt->metadata['max_employees'],
                'coupon_code' => $initial ? ($attempt->metadata['coupon_code'] ?? null) : null,
                'paid_through' => $end->toIso8601String(), 'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null],
        ]);
        if ($initial && $attempt->coupon_id && empty($attempt->metadata['coupon_redeemed'])) {
            $coupon = Coupon::whereKey($attempt->coupon_id)->lockForUpdate()->firstOrFail();
            $coupon->increment('redeemed_count');
            $attempt->metadata = array_merge($attempt->metadata, ['coupon_redeemed' => true]);
        }
        $attempt->status = 'completed';
        $attempt->save();
        if (! $tenant->subscription_ends_at || $end->greaterThan($tenant->subscription_ends_at)
            || $tenant->billing_provider !== 'stripe' || $tenant->billing_subscription_id !== $subscriptionId) {
            $tenant->update(['package_id' => $attempt->package_id, 'billing_cycle' => $attempt->billing_cycle,
                'subscription_ends_at' => $end]);
        }
        $this->audit->log('stripe_invoice_paid', TenantPayment::class, $payment->id,
            ['tenant_id' => $tenant->id, 'source' => 'stripe', 'after' => ['invoice_id' => $invoice['id'], 'amount' => $payment->amount]]);

        return $payment;
    }

    protected function assertSubscription(array $subscription, StripeCheckoutAttempt $attempt, Tenant $tenant): void
    {
        $metadata = $subscription['metadata'] ?? [];
        $price = data_get($subscription, 'items.data.0.price', []);
        if ($this->id($subscription['customer'] ?? null) !== $tenant->stripe_customer_id
            || ($metadata['tenant_id'] ?? null) !== (string) $tenant->id
            || ($metadata['checkout_attempt_id'] ?? null) !== $attempt->id
            || ($metadata['package_id'] ?? null) !== (string) $attempt->package_id
            || ($metadata['billing_cycle'] ?? null) !== $attempt->billing_cycle
            || count(data_get($subscription, 'items.data', [])) !== 1
            || (int) ($price['unit_amount'] ?? -1) !== (int) round((float) $attempt->base_amount * 100)
            || ($price['currency'] ?? null) !== 'usd'
            || data_get($price, 'recurring.interval') !== ($attempt->billing_cycle === 'yearly' ? 'year' : 'month')) {
            throw new DomainException('Stripe subscription does not match the agreed tenant checkout.');
        }
    }

    protected function assertSession(array $session, StripeCheckoutAttempt $attempt, Tenant $tenant): void
    {
        if ($this->id($session['customer'] ?? null) !== $tenant->stripe_customer_id
            || data_get($session, 'metadata.tenant_id') !== (string) $tenant->id
            || ($attempt->stripe_session_id && $attempt->stripe_session_id !== $session['id'])) {
            throw new DomainException('Checkout session does not match the tenant attempt.');
        }
    }

    protected function recordEvent(array $event): void
    {
        StripeWebhookEvent::firstOrCreate(['id' => $event['id']], ['event_type' => $event['type'], 'processed_at' => now()]);
    }

    protected function id(mixed $value): ?string
    {
        return is_array($value) ? ($value['id'] ?? null) : (is_string($value) ? $value : null);
    }
}
