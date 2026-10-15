<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Package;
use App\Models\StripeCheckoutAttempt;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Billing\StripeCheckoutService;
use App\Services\Billing\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.secret' => 'sk_test_placeholder', 'services.stripe.webhook_secret' => 'whsec_placeholder']);
    }

    public function test_gateway_constructs_supported_sdk_client(): void
    {
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $gateway = new class extends StripeGateway
        {
            public function sdk(): StripeClient
            {
                return $this->client();
            }
        };
        $this->assertInstanceOf(StripeClient::class, $gateway->sdk());
    }

    public function test_real_stripe_ids_are_distinct_from_simulated_history(): void
    {
        $this->assertTrue(Schema::hasColumns('tenants', ['stripe_customer_id', 'stripe_subscription_id', 'stripe_status', 'stripe_cancel_at_period_end']));
        $tenant = Tenant::createWithSlug('Legacy billing', 'legacy-billing');
        $tenant->update(['billing_customer_id' => 'cus_fake_history', 'billing_provider' => 'stripe']);
        $this->assertNull($tenant->fresh()->stripe_customer_id);
        $tenant->update(['stripe_customer_id' => 'cus_real', 'stripe_cancel_at_period_end' => true]);
        $this->assertSame('cus_real', $tenant->fresh()->stripe_customer_id);
        $this->assertTrue($tenant->fresh()->stripe_cancel_at_period_end);
    }

    public function test_invoice_identity_and_checkout_snapshot_are_persisted(): void
    {
        $this->assertTrue(Schema::hasColumns('tenant_payments', ['stripe_invoice_id', 'stripe_subscription_id', 'stripe_checkout_attempt_id']));
        $this->assertTrue(Schema::hasColumns('stripe_checkout_attempts', ['id', 'tenant_id', 'base_amount', 'discount_amount', 'total_amount', 'stripe_session_id', 'expires_at']));
        $this->assertTrue(Schema::hasColumns('stripe_webhook_events', ['id', 'event_type', 'processed_at']));
        $this->assertContains('stripe_invoice_id', (new TenantPayment)->getFillable());
    }

    public function test_checkout_is_recurring_and_does_not_activate(): void
    {
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $tenant = Tenant::createWithSlug('Checkout', 'checkout');
        $package = Package::where('slug', 'growth')->firstOrFail();
        $coupon = Coupon::create(['code' => 'FIRST20', 'name' => 'First invoice', 'discount_type' => 'percentage', 'discount_value' => 20, 'is_active' => true]);
        $gateway = $this->mock(StripeGateway::class);
        $gateway->shouldReceive('customer')->once()->andReturn('cus_checkout');
        $gateway->shouldReceive('coupon')->once()->withArgs(fn ($params, $key) => $params['duration'] === 'once' && $params['amount_off'] === 1580)->andReturn('coupon_once');
        $gateway->shouldReceive('checkout')->once()->withArgs(function ($params, $key) use ($tenant) {
            $this->assertSame('subscription', $params['mode']);
            $this->assertSame(7900, $params['line_items'][0]['price_data']['unit_amount']);
            $this->assertSame('month', $params['line_items'][0]['price_data']['recurring']['interval']);
            $this->assertSame((string) $tenant->id, $params['subscription_data']['metadata']['tenant_id']);

            return true;
        })->andReturn(['id' => 'cs_test_checkout', 'url' => 'https://checkout.stripe.com/test']);
        $url = app(StripeCheckoutService::class)->start($tenant, $package, 'monthly', 'FIRST20', 'admin@example.test');
        $this->assertSame('https://checkout.stripe.com/test', $url);
        $this->assertSame('trial', $tenant->fresh()->subscription_status);
        $this->assertSame(0, TenantPayment::withoutGlobalScopes()->count());
        $this->assertSame(0, $coupon->fresh()->redeemed_count);
        $this->assertDatabaseHas('stripe_checkout_attempts', ['tenant_id' => $tenant->id, 'status' => 'open', 'total_amount' => 63.20]);
    }

    public function test_checkout_retries_same_attempt_after_timeout_and_catalog_edit(): void
    {
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $tenant = Tenant::createWithSlug('Retry', 'retry');
        $package = Package::where('slug', 'growth')->firstOrFail();
        $gateway = $this->mock(StripeGateway::class);
        $gateway->shouldReceive('customer')->andReturn('cus_retry');
        $requests = [];
        $gateway->shouldReceive('checkout')->twice()->andReturnUsing(function ($params, $key) use (&$requests) {
            $requests[] = [$params, $key];
            if (count($requests) === 1) {
                throw new \RuntimeException('Remote response timed out');
            }

            return ['id' => 'cs_test_retry', 'url' => 'https://checkout.stripe.com/retry'];
        });
        try {
            app(StripeCheckoutService::class)->start($tenant, $package, 'yearly', null, 'admin@example.test');
            $this->fail('Expected gateway timeout');
        } catch (\RuntimeException $e) {
            $this->assertSame('Remote response timed out', $e->getMessage());
        }
        $package->update(['price_yearly' => 999]);
        app(StripeCheckoutService::class)->start($tenant->fresh(), $package->fresh(), 'yearly', null, 'admin@example.test');
        $this->assertSame($requests[0], $requests[1]);
        $this->assertSame(1, StripeCheckoutAttempt::withoutGlobalScopes()->count());
    }

    public function test_checkout_blocks_second_live_subscription(): void
    {
        $tenant = Tenant::createWithSlug('Subscribed', 'subscribed');
        $tenant->update(['stripe_subscription_id' => 'sub_live', 'stripe_status' => 'past_due']);
        $this->expectException(ValidationException::class);
        app(StripeCheckoutService::class)->start($tenant, Package::first(), 'monthly', null, 'admin@example.test');
    }

    public function test_checkout_rejects_inactive_package_and_invalid_coupon(): void
    {
        $tenant = Tenant::createWithSlug('Invalid', 'invalid');
        $package = Package::first();
        $package->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(StripeCheckoutService::class)->start($tenant, $package, 'monthly', 'INVALID', 'admin@example.test');
    }

    public function test_limited_coupon_counts_open_reservations(): void
    {
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $coupon = Coupon::create(['code' => 'ONLYONE', 'name' => 'One redemption', 'discount_type' => 'fixed', 'discount_value' => 1, 'is_active' => true, 'max_redemptions' => 1]);
        $gateway = $this->mock(StripeGateway::class);
        $gateway->shouldReceive('customer')->once()->andReturn('cus_one');
        $gateway->shouldReceive('coupon')->once()->andReturn('coupon_one');
        $gateway->shouldReceive('checkout')->once()->andReturn(['id' => 'cs_test_one', 'url' => 'https://checkout.stripe.com/one']);
        app(StripeCheckoutService::class)->start(Tenant::createWithSlug('One', 'one'), Package::first(), 'monthly', 'ONLYONE', 'admin@example.test');
        $this->expectException(ValidationException::class);
        app(StripeCheckoutService::class)->start(Tenant::createWithSlug('Two', 'two'), Package::first(), 'monthly', 'ONLYONE', 'admin@example.test');
    }

    public function test_checkout_return_does_not_activate(): void
    {
        $tenant = Tenant::createWithSlug('Return', 'return-test');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get('/admin/billing/stripe/success')->assertRedirect(route('billing.index'));
        $this->assertSame('trial', $tenant->fresh()->subscription_status);
        $this->assertSame(0, TenantPayment::withoutGlobalScopes()->count());
    }

    public function test_browser_starts_hosted_checkout_without_card_details(): void
    {
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $tenant = Tenant::createWithSlug('Browser', 'browser');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN]);
        $this->mock(StripeCheckoutService::class)->shouldReceive('start')->once()->andReturn('https://checkout.stripe.com/browser');
        $this->actingAs($admin)->post(route('billing.stripe'), ['package_id' => Package::first()->id, 'billing_cycle' => 'monthly'])
            ->assertRedirect('https://checkout.stripe.com/browser');
        $this->assertSame('trial', $tenant->fresh()->subscription_status);
    }

    public function test_cleaner_cannot_change_billing(): void
    {
        $tenant = Tenant::createWithSlug('Cleaner', 'cleaner-billing');
        $cleaner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_CLEANER]);
        $this->actingAs($cleaner)->post(route('billing.stripe'), ['package_id' => Package::first()->id, 'billing_cycle' => 'monthly'])->assertForbidden();
    }

    public function test_canceled_stripe_tenant_cannot_restore_access_through_simulated_paypal(): void
    {
        $tenant = Tenant::createWithSlug('Canceled', 'canceled-paypal');
        $tenant->update(['stripe_customer_id' => 'cus_canceled', 'stripe_subscription_id' => 'sub_canceled',
            'stripe_status' => 'canceled', 'subscription_status' => 'canceled']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->post(route('billing.paypal'), ['package_id' => Package::first()->id, 'billing_cycle' => 'monthly'])
            ->assertSessionHasErrors('billing');
        $this->assertSame('canceled', $tenant->fresh()->subscription_status);
        $this->assertSame(0, TenantPayment::withoutGlobalScopes()->count());
    }

    public function test_platform_admin_requires_selected_tenant_for_checkout(): void
    {
        $admin = User::factory()->create(['tenant_id' => null, 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->post(route('billing.stripe'), ['package_id' => Package::first()->id, 'billing_cycle' => 'monthly'])->assertForbidden();
    }

    public function test_portal_uses_only_authenticated_tenant_customer(): void
    {
        $tenant = Tenant::createWithSlug('Portal', 'portal');
        $tenant->update(['stripe_customer_id' => 'cus_portal']);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN]);
        $this->mock(StripeGateway::class)->shouldReceive('portal')->once()->with('cus_portal', route('billing.index'))->andReturn('https://billing.stripe.com/portal');
        $this->actingAs($admin)->post('/admin/billing/stripe/portal', ['customer_id' => 'cus_foreign'])->assertRedirect('https://billing.stripe.com/portal');
    }

    public function test_billing_page_has_no_raw_card_fields(): void
    {
        $tenant = Tenant::createWithSlug('Secure form', 'secure-form');
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->get(route('billing.index'))->assertOk()
            ->assertDontSee('name="card_number"', false)->assertDontSee('name="card_cvc"', false)
            ->assertSee('renews automatically');
    }

    public function test_tenant_admin_cannot_send_another_tenants_invoice(): void
    {
        $own = Tenant::createWithSlug('Own', 'own-invoice');
        $other = Tenant::createWithSlug('Other', 'other-invoice');
        $admin = User::factory()->create(['tenant_id' => $own->id, 'role' => User::ROLE_ADMIN]);
        $payment = TenantPayment::create(['tenant_id' => $other->id, 'package_id' => Package::first()->id,
            'payment_gateway' => 'stripe', 'gateway_transaction_id' => 'in_foreign', 'amount' => 29,
            'currency' => 'USD', 'billing_cycle' => 'monthly', 'status' => 'completed']);
        $response = $this->actingAs($admin)->post(route('billing.invoices.send', $payment));
        $this->assertContains($response->status(), [403, 404]);
        $this->assertNull($payment->fresh()->invoice_sent_at);
    }
}
