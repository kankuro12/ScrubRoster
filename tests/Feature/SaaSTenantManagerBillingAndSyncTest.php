<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaaSTenantManagerBillingAndSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AdminUserSeeder::class);
    }

    public function test_front_website_is_publicly_accessible_with_packages(): void
    {
        $response = $this->get(route('root'));

        $response->assertOk()
            ->assertSee('ScrubRoster')
            ->assertSee('Field-Ops SaaS')
            ->assertSee('Starter Tier')
            ->assertSee('Growth Fleet');
    }

    public function test_tenant_manager_page_accessible_by_platform_admin(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $response = $this->actingAs($admin)->get(route('tenant-manager.index'));

        $response->assertOk()
            ->assertSee('Tenant Manager')
            ->assertSee('All Tenant Workspaces')
            ->assertSee('Packages & Plans', false)
            ->assertSee('Promo Coupons');
    }

    public function test_tenant_manager_can_create_package_and_coupon(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        // 1. Create Package
        $packageResponse = $this->actingAs($admin)->post(route('tenant-manager.packages.store'), [
            'name' => 'Custom Mega Fleet',
            'description' => 'Custom tier for high-volume enterprise',
            'price_monthly' => 149.00,
            'price_yearly' => 1490.00,
            'max_employees' => 60,
            'trial_days' => 21,
            'has_google_calendar' => 1,
            'has_google_sheets' => 1,
            'has_qr_login' => 1,
        ]);
        $packageResponse->assertRedirect(route('tenant-manager.index', ['tab' => 'packages']));
        $this->assertDatabaseHas('packages', ['name' => 'Custom Mega Fleet', 'max_employees' => 60]);

        // 2. Create Coupon
        $couponResponse = $this->actingAs($admin)->post(route('tenant-manager.coupons.store'), [
            'code' => 'SUPER30',
            'name' => 'Super 30% Off',
            'discount_type' => 'percentage',
            'discount_value' => 30.00,
            'max_redemptions' => 150,
        ]);
        $couponResponse->assertRedirect(route('tenant-manager.index', ['tab' => 'coupons']));
        $this->assertDatabaseHas('coupons', ['code' => 'SUPER30', 'discount_value' => 30.00]);
    }

    public function test_coupon_validation_ajax_calculates_discount(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        Coupon::create([
            'code' => 'TEST50',
            'name' => '$50 Off',
            'discount_type' => 'fixed',
            'discount_value' => 50.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('billing.validate-coupon'), [
            'code' => 'TEST50',
            'amount' => 100.00,
        ]);

        $response->assertOk()
            ->assertJson([
                'valid' => true,
                'discount' => 50.00,
                'final_amount' => 50.00,
            ]);
    }

    public function test_tenant_admin_can_upgrade_via_stripe_with_coupon(): void
    {
        $tenant = Tenant::createWithSlug('Stripe Test Tenant', 'stripe-test');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $growthPackage = Package::where('slug', 'growth')->first();
        Coupon::create([
            'code' => 'UPGRADE20',
            'name' => '20% off',
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('billing.stripe'), [
            'package_id' => $growthPackage->id,
            'billing_cycle' => 'monthly',
            'coupon_code' => 'UPGRADE20',
            'card_name' => 'John Clean',
            'card_number' => '4242 4242 4242 4242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
        ]);

        $response->assertRedirect(route('billing.index'));

        $tenant->refresh();
        $this->assertEquals($growthPackage->id, $tenant->package_id);
        $this->assertEquals('active', $tenant->subscription_status);
        $this->assertEquals('stripe', $tenant->billing_provider);

        $this->assertDatabaseHas('tenant_payments', [
            'tenant_id' => $tenant->id,
            'package_id' => $growthPackage->id,
            'payment_gateway' => 'stripe',
            'status' => 'completed',
        ]);
    }

    public function test_tenant_admin_can_checkout_via_paypal(): void
    {
        $tenant = Tenant::createWithSlug('PayPal Test Tenant', 'paypal-test');
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $enterprisePackage = Package::where('slug', 'enterprise')->first();

        $response = $this->actingAs($admin)->post(route('billing.paypal'), [
            'package_id' => $enterprisePackage->id,
            'billing_cycle' => 'yearly',
            'paypal_payer_email' => 'finance@paypal-test.test',
        ]);

        $response->assertRedirect(route('billing.index'));

        $tenant->refresh();
        $this->assertEquals($enterprisePackage->id, $tenant->package_id);
        $this->assertEquals('active', $tenant->subscription_status);
        $this->assertEquals('paypal', $tenant->billing_provider);

        $this->assertDatabaseHas('tenant_payments', [
            'tenant_id' => $tenant->id,
            'package_id' => $enterprisePackage->id,
            'payment_gateway' => 'paypal',
            'status' => 'completed',
        ]);
    }

    public function test_google_sync_feeds_return_valid_ical_and_csv(): void
    {
        $tenant = Tenant::createWithSlug('Sync Test Tenant', 'sync-test');
        $token = $tenant->ensureGoogleSyncToken();

        // 1. Test iCal Feed (.ics)
        $icalResponse = $this->get(route('integrations.google.calendar.feed', ['token' => $token]));
        $icalResponse->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertSee('BEGIN:VCALENDAR')
            ->assertSee('END:VCALENDAR');

        // 2. Test Sheets CSV Feed
        $csvResponse = $this->get(route('integrations.google.sheets.feed', ['token' => $token, 'type' => 'attendance']));
        $csvResponse->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertSee('Event ID')
            ->assertSee('Personnel Name')
            ->assertSee('Event Type');
    }

    public function test_operations_manual_page_accessible(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        $response = $this->actingAs($admin)->get(route('manual.index'));

        $response->assertOk()
            ->assertSee('Field Operations & Platform Manual')
            ->assertSee('Google Calendar Automatic Sync')
            ->assertSee('Google Sheets Live Data Sync');
    }
}
