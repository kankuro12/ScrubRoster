<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\Package;
use App\Models\Coupon;
use App\Models\StripeCheckoutAttempt;
use App\Models\User;
use App\Services\Billing\StripeCheckoutService;
use App\Services\Billing\StripeGateway;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StripeBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_constructs_supported_sdk_client(): void
    {
        config(['services.stripe.secret' => 'sk_test_placeholder']);
        $gateway = new class extends StripeGateway
        {
            public function sdk(): \Stripe\StripeClient
            {
                return $this->client();
            }
        };
        $this->assertInstanceOf(\Stripe\StripeClient::class, $gateway->sdk());
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
}
