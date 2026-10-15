<?php

namespace App\Services\Billing;

use App\Models\Coupon;
use App\Models\Package;
use App\Models\StripeCheckoutAttempt;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\InvalidRequestException;

class StripeCheckoutService
{
    public function __construct(protected StripeGateway $gateway) {}

    public function start(Tenant $tenant, Package $package, string $cycle, ?string $couponCode, string $email): string
    {
        if (! filled(config('services.stripe.secret')) || ! filled(config('services.stripe.webhook_secret'))) {
            throw new \RuntimeException('Stripe payments and webhook confirmation must both be configured.');
        }
        $couponCode = $couponCode ? strtoupper(trim($couponCode)) : null;
        $attempt = DB::transaction(function () use ($tenant, $package, $cycle, $couponCode, $email) {
            $tenant = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            if ($tenant->stripe_subscription_id && ! in_array($tenant->stripe_status, ['canceled', 'incomplete_expired'], true)) {
                throw ValidationException::withMessages(['billing' => 'Manage your existing Stripe subscription before starting another.']);
            }

            $existing = StripeCheckoutAttempt::withoutGlobalScopes()->where('tenant_id', $tenant->id)
                ->whereIn('status', ['creating', 'open'])->lockForUpdate()->first();
            if ($existing) {
                if ($existing->package_id !== $package->id || $existing->billing_cycle !== $cycle || ($existing->metadata['coupon_code'] ?? null) !== $couponCode) {
                    throw ValidationException::withMessages(['billing' => 'A checkout is already pending. Complete it or wait for Stripe to confirm its expiry.']);
                }

                return $existing;
            }

            $package = Package::whereKey($package->id)->firstOrFail();
            if (! $package->is_active || ! in_array($cycle, ['monthly', 'yearly'], true)) {
                throw ValidationException::withMessages(['package_id' => 'Choose an active plan and valid billing cycle.']);
            }
            $limit = $tenant->max_employees_override ?? $package->max_employees;
            if ($limit > 0 && $tenant->getActiveEmployeesCount() > $limit) {
                throw ValidationException::withMessages(['package_id' => 'This plan cannot cover your current employee count.']);
            }

            $base = (float) ($cycle === 'yearly' ? $package->price_yearly : $package->price_monthly);
            $coupon = $couponCode ? Coupon::where('code', $couponCode)->lockForUpdate()->first() : null;
            if ($couponCode && (! $coupon || ! $coupon->isValid())) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon is invalid, expired or fully redeemed.']);
            }
            if ($coupon && $coupon->max_redemptions !== null) {
                // Unknown remote outcomes stay reserved until a signed expiry or paid invoice resolves them.
                $reserved = StripeCheckoutAttempt::withoutGlobalScopes()->where('coupon_id', $coupon->id)
                    ->whereIn('status', ['creating', 'open', 'awaiting_payment'])
                    ->lockForUpdate()->get(['id'])->count();
                if ($coupon->redeemed_count + $reserved >= $coupon->max_redemptions) {
                    throw ValidationException::withMessages(['coupon_code' => 'All remaining coupon uses are reserved by pending checkouts.']);
                }
            }
            $discount = $coupon?->calculateDiscount($base) ?? 0.0;

            return StripeCheckoutAttempt::create([
                'id' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'package_id' => $package->id,
                'coupon_id' => $coupon?->id, 'billing_cycle' => $cycle, 'base_amount' => $base,
                'discount_amount' => $discount, 'total_amount' => round($base - $discount, 2),
                'currency' => 'USD', 'status' => 'creating', 'expires_at' => now()->addMinutes(31),
                'metadata' => ['package_name' => $package->name, 'max_employees' => $package->max_employees,
                    'coupon_code' => $couponCode, 'email' => $email,
                    'success_url' => route('billing.stripe.success'), 'cancel_url' => route('billing.stripe.cancel')],
            ]);
        }, 3);

        if ($attempt->status === 'open') {
            if ($attempt->expires_at->isPast()) {
                $remote = $this->gateway->session($attempt->stripe_session_id);
                if (($remote['status'] ?? null) === 'expired') {
                    $attempt->update(['status' => 'expired']);
                    throw ValidationException::withMessages(['billing' => 'Your previous checkout expired. Please select your plan again.']);
                }
            }

            return $attempt->metadata['checkout_url'];
        }
        if ($attempt->expires_at->isPast()) {
            throw ValidationException::withMessages(['billing' => 'A previous Stripe request needs reconciliation. Contact your administrator before retrying.']);
        }

        try {
            $customer = $this->gateway->customer($tenant->fresh());
            Tenant::whereKey($tenant->id)->update(['stripe_customer_id' => $customer]);
            $metadata = ['tenant_id' => (string) $tenant->id, 'package_id' => (string) $attempt->package_id,
                'checkout_attempt_id' => $attempt->id, 'billing_cycle' => $attempt->billing_cycle];
            $parameters = [
                'mode' => 'subscription', 'customer' => $customer, 'client_reference_id' => (string) $tenant->id,
                'payment_method_types' => ['card'], 'metadata' => $metadata,
                'subscription_data' => ['metadata' => $metadata],
                'line_items' => [['quantity' => 1, 'price_data' => ['currency' => 'usd',
                    'unit_amount' => (int) round((float) $attempt->base_amount * 100),
                    'recurring' => ['interval' => $attempt->billing_cycle === 'yearly' ? 'year' : 'month'],
                    'product_data' => ['name' => $attempt->metadata['package_name']]]]],
                'success_url' => $attempt->metadata['success_url'], 'cancel_url' => $attempt->metadata['cancel_url'],
                'expires_at' => $attempt->expires_at->getTimestamp(),
            ];
            if ((float) $attempt->discount_amount > 0) {
                $coupon = $this->gateway->coupon(['duration' => 'once', 'currency' => 'usd',
                    'amount_off' => (int) round((float) $attempt->discount_amount * 100)], 'scrubroster-coupon-'.$attempt->id);
                $parameters['discounts'] = [['coupon' => $coupon]];
            }
            $session = $this->gateway->checkout($parameters, 'scrubroster-checkout-'.$attempt->id);
        } catch (AuthenticationException|InvalidRequestException $e) {
            $attempt->update(['status' => 'failed']);
            throw $e;
        }

        DB::transaction(function () use ($attempt, $session): void {
            $locked = StripeCheckoutAttempt::withoutGlobalScopes()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $locked->update(['stripe_session_id' => $session['id'],
                'status' => $locked->status === 'creating' ? 'open' : $locked->status,
                'metadata' => array_merge($locked->metadata, ['checkout_url' => $session['url']])]);
        });

        return $session['url'];
    }
}
