<?php

namespace Tests\Feature;

use App\Jobs\SendTenantPaymentInvoice;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\StripeCheckoutAttempt;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Services\Billing\StripeGateway;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private StripeCheckoutAttempt $attempt;

    private Coupon $coupon;

    private array $subscription;

    private array $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        config(['services.stripe.webhook_secret' => 'whsec_fixture']);
        $this->tenant = Tenant::createWithSlug('Webhook tenant', 'webhook-tenant');
        $this->tenant->update(['stripe_customer_id' => 'cus_fixture']);
        $this->coupon = Coupon::create(['code' => 'ONCE20', 'name' => 'Once', 'discount_type' => 'percentage', 'discount_value' => 20, 'is_active' => true]);
        $package = Package::where('slug', 'growth')->firstOrFail();
        $this->attempt = StripeCheckoutAttempt::create(['id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id,
            'package_id' => $package->id, 'coupon_id' => $this->coupon->id, 'billing_cycle' => 'monthly',
            'base_amount' => 79, 'discount_amount' => 15.80, 'total_amount' => 63.20, 'currency' => 'USD',
            'status' => 'open', 'stripe_session_id' => 'cs_test_fixture', 'expires_at' => now()->addMinutes(30),
            'metadata' => ['package_name' => 'Growth Fleet', 'max_employees' => 25, 'coupon_code' => 'ONCE20']]);
        $this->subscription = ['id' => 'sub_fixture', 'customer' => 'cus_fixture', 'status' => 'active',
            'cancel_at_period_end' => false, 'metadata' => ['tenant_id' => (string) $this->tenant->id,
                'package_id' => (string) $package->id, 'checkout_attempt_id' => $this->attempt->id, 'billing_cycle' => 'monthly'],
            'latest_invoice' => ['id' => 'in_initial', 'status' => 'paid'],
            'items' => ['data' => [['current_period_end' => now()->addMonth()->timestamp,
                'price' => ['unit_amount' => 7900, 'currency' => 'usd', 'recurring' => ['interval' => 'month']]]]]];
        $this->invoice = ['id' => 'in_initial', 'object' => 'invoice', 'status' => 'paid', 'customer' => 'cus_fixture',
            'parent' => ['subscription_details' => ['subscription' => 'sub_fixture']], 'currency' => 'usd',
            'amount_paid' => 6320, 'total' => 6320, 'subtotal' => 7900, 'total_discount_amounts' => [['amount' => 1580]],
            'billing_reason' => 'subscription_create', 'lines' => ['data' => [['amount' => 7900,
                'period' => ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp]]]]];
        $gateway = $this->mock(StripeGateway::class);
        $gateway->shouldReceive('subscription')->andReturnUsing(fn () => $this->subscription);
        $gateway->shouldReceive('invoice')->andReturnUsing(fn () => $this->invoice);
    }

    private function event(string $type = 'invoice.paid', string $id = 'evt_initial', ?array $object = null): TestResponse
    {
        $payload = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'data' => ['object' => $object ?? $this->invoice]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_fixture');

        return $this->call('POST', '/integrations/stripe/webhook', [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => "t=$timestamp,v1=$signature"], $payload);
    }

    public function test_signed_paid_invoice_activates_before_checkout_event_and_replays_once(): void
    {
        app(TenantContext::class)->setTenant(Tenant::createWithSlug('Wrong context', 'wrong-context'));
        $this->event()->assertOk();
        $this->event()->assertOk();
        $this->event('invoice.paid', 'evt_different_same_invoice')->assertOk();
        $tenant = $this->tenant->fresh();
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame($this->attempt->package_id, $tenant->package_id);
        $this->assertSame('sub_fixture', $tenant->stripe_subscription_id);
        $this->assertSame(1, TenantPayment::withoutGlobalScopes()->count());
        $this->assertSame(1, $this->coupon->fresh()->redeemed_count);
        Queue::assertPushed(SendTenantPaymentInvoice::class, 1);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'stripe_invoice_paid']);
    }

    public function test_invalid_signature_cannot_activate(): void
    {
        $this->postJson('/integrations/stripe/webhook', ['type' => 'invoice.paid'], ['Stripe-Signature' => 'invalid'])->assertStatus(400);
        $this->assertSame('trial', $this->tenant->fresh()->subscription_status);
        $this->assertDatabaseCount('tenant_payments', 0);
    }

    public function test_missing_webhook_secret_fails_closed(): void
    {
        config(['services.stripe.webhook_secret' => null]);
        $this->event()->assertStatus(503);
        $this->assertDatabaseCount('tenant_payments', 0);
    }

    public function test_foreign_customer_is_rejected_without_payment_mutation(): void
    {
        $this->invoice['customer'] = 'cus_foreign';
        $this->event()->assertStatus(422);
        $this->assertDatabaseCount('tenant_payments', 0);
    }

    public function test_unhandled_signed_event_is_acknowledged(): void
    {
        $this->event('customer.updated', 'evt_unhandled', ['id' => 'cus_fixture'])->assertOk();
        $this->assertDatabaseCount('tenant_payments', 0);
    }

    public function test_checkout_completion_alone_does_not_activate(): void
    {
        $this->event('checkout.session.completed', 'evt_checkout', ['id' => 'cs_test_fixture',
            'customer' => 'cus_fixture', 'subscription' => 'sub_fixture', 'metadata' => $this->subscription['metadata']])->assertOk();
        $this->assertSame('trial', $this->tenant->fresh()->subscription_status);
        $this->assertDatabaseCount('tenant_payments', 0);
    }

    public function test_failed_renewal_does_not_extend_access_and_recovers(): void
    {
        $this->event()->assertOk();
        $paidThrough = $this->tenant->fresh()->subscription_ends_at;
        $this->invoice['id'] = 'in_renewal';
        $this->invoice['status'] = 'open';
        $this->subscription['status'] = 'past_due';
        $this->subscription['latest_invoice'] = ['id' => 'in_renewal', 'status' => 'open'];
        $this->event('invoice.payment_failed', 'evt_failure')->assertOk();
        $this->assertTrue($paidThrough->equalTo($this->tenant->fresh()->subscription_ends_at));
        $this->assertTrue($this->tenant->fresh()->isSubscriptionActive());
        $this->travelTo($paidThrough->copy()->addSecond());
        $this->assertFalse($this->tenant->fresh()->isSubscriptionActive());
        $this->subscription['status'] = 'active';
        $this->subscription['latest_invoice']['status'] = 'paid';
        $this->invoice['status'] = 'paid';
        $this->invoice['billing_reason'] = 'subscription_cycle';
        $this->invoice['amount_paid'] = $this->invoice['total'] = 7900;
        $this->invoice['total_discount_amounts'] = [];
        $this->invoice['lines']['data'][0]['period']['end'] = now()->addMonth()->timestamp;
        $this->event('invoice.paid', 'evt_recovered')->assertOk();
        $this->assertSame('active', $this->tenant->fresh()->subscription_status);
        $this->assertTrue($this->tenant->fresh()->isSubscriptionActive());
        $this->assertDatabaseCount('tenant_payments', 2);
        $this->assertSame(1, $this->coupon->fresh()->redeemed_count);
    }

    public function test_scheduled_cancellation_preserves_paid_period_then_ends(): void
    {
        $this->event()->assertOk();
        $this->subscription['cancel_at_period_end'] = true;
        $this->event('customer.subscription.updated', 'evt_scheduled', $this->subscription)->assertOk();
        $this->assertTrue($this->tenant->fresh()->stripe_cancel_at_period_end);
        $this->assertTrue($this->tenant->fresh()->isSubscriptionActive());
        $this->subscription['status'] = 'canceled';
        $this->event('customer.subscription.deleted', 'evt_deleted', $this->subscription)->assertOk();
        $this->assertFalse($this->tenant->fresh()->isSubscriptionActive());
        $stale = $this->subscription;
        $stale['status'] = 'active';
        $this->event('customer.subscription.updated', 'evt_stale', $stale)->assertOk();
        $this->assertSame('canceled', $this->tenant->fresh()->stripe_status);
        $this->assertFalse($this->tenant->fresh()->isSubscriptionActive());
    }

    public function test_zero_total_initial_paid_invoice_is_supported(): void
    {
        $this->attempt->update(['discount_amount' => 79, 'total_amount' => 0]);
        $this->invoice['amount_paid'] = $this->invoice['total'] = 0;
        $this->invoice['total_discount_amounts'] = [['amount' => 7900]];
        $this->event()->assertOk();
        $this->assertSame('active', $this->tenant->fresh()->subscription_status);
        $this->assertDatabaseHas('tenant_payments', ['amount' => 0, 'status' => 'completed']);
    }

    public function test_yearly_payment_uses_paid_invoice_period(): void
    {
        $this->attempt->update(['billing_cycle' => 'yearly', 'base_amount' => 790, 'discount_amount' => 0, 'total_amount' => 790, 'coupon_id' => null]);
        $this->subscription['metadata']['billing_cycle'] = 'yearly';
        $this->subscription['items']['data'][0]['price']['unit_amount'] = 79000;
        $this->subscription['items']['data'][0]['price']['recurring']['interval'] = 'year';
        $this->invoice['amount_paid'] = $this->invoice['total'] = $this->invoice['subtotal'] = 79000;
        $this->invoice['total_discount_amounts'] = [];
        $end = now()->addYear()->timestamp;
        $this->invoice['lines']['data'][0]['period']['end'] = $end;
        $this->event()->assertOk();
        $this->assertSame($end, $this->tenant->fresh()->subscription_ends_at->timestamp);
    }

    public function test_expired_checkout_releases_reservation_without_activation(): void
    {
        $this->event('checkout.session.expired', 'evt_expired', ['id' => 'cs_test_fixture',
            'customer' => 'cus_fixture', 'metadata' => $this->subscription['metadata']])->assertOk();
        $this->assertSame('expired', $this->attempt->fresh()->status);
        $this->assertSame('trial', $this->tenant->fresh()->subscription_status);
    }

    public function test_late_paid_invoice_for_retired_subscription_is_recorded_without_replacing_current_access(): void
    {
        $this->event()->assertOk();
        $paidThrough = now()->addDays(60)->startOfSecond();
        $this->tenant->update(['stripe_subscription_id' => 'sub_replacement', 'stripe_status' => 'active',
            'billing_subscription_id' => 'sub_replacement', 'subscription_ends_at' => $paidThrough]);
        $this->subscription['status'] = 'canceled';
        $this->invoice['id'] = 'in_retired_late';
        $this->invoice['billing_reason'] = 'subscription_cycle';
        $this->invoice['total'] = $this->invoice['amount_paid'] = 7900;
        $this->invoice['total_discount_amounts'] = [];
        $this->invoice['lines']['data'][0]['period']['end'] = now()->addDays(90)->timestamp;
        $this->event('invoice.paid', 'evt_retired_late')->assertOk();
        $this->event('invoice.paid', 'evt_retired_replay')->assertOk();
        $this->assertDatabaseCount('tenant_payments', 2);
        $this->assertSame('sub_replacement', $this->tenant->fresh()->stripe_subscription_id);
        $this->assertTrue($this->tenant->fresh()->subscription_ends_at->equalTo($paidThrough));
        $this->assertSame(1, $this->coupon->fresh()->redeemed_count);
    }

    public function test_unpaid_terminal_subscription_releases_coupon_reservation(): void
    {
        $this->subscription['status'] = 'incomplete';
        $this->subscription['latest_invoice']['status'] = 'open';
        $this->event('checkout.session.completed', 'evt_incomplete', ['id' => 'cs_test_fixture',
            'customer' => 'cus_fixture', 'subscription' => 'sub_fixture', 'metadata' => $this->subscription['metadata']])->assertOk();
        $this->subscription['status'] = 'incomplete_expired';
        $this->event('customer.subscription.updated', 'evt_terminal', $this->subscription)->assertOk();
        $this->assertSame('failed', $this->attempt->fresh()->status);
        $this->assertSame(0, $this->coupon->fresh()->redeemed_count);
        $this->assertSame('trial', $this->tenant->fresh()->subscription_status);
    }
}
