<?php

namespace Tests\Feature;

use App\Mail\TenantAlertMail;
use App\Mail\TenantInvoiceMail;
use App\Mail\TenantQueryMail;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantAlert;
use App\Models\TenantPayment;
use App\Models\TenantQuery;
use App\Models\TenantQueryMessage;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use App\Services\Tenants\TenantAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenantQueriesAndAlertsAndInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $tenantAdmin;
    protected User $superAdmin;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a tenant organization
        $this->tenant = Tenant::create([
            'name' => 'Acme Cleaners',
            'slug' => 'acme',
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(2),
        ]);

        // 2. Create tenant admin
        $this->tenantAdmin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alice Admin',
            'email' => 'alice@acme.test',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_ADMIN, // Tenant Admin
        ]);

        // 3. Create Super Admin (tenant_id = null)
        $this->superAdmin = User::create([
            'tenant_id' => null,
            'name' => 'Super Platform Admin',
            'email' => 'platform@scrubroster.test',
            'password' => bcrypt('secret123'),
            'role' => User::ROLE_ADMIN,
        ]);

        // 4. Create or retrieve Package
        $this->package = Package::firstOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth Plan',
                'price_monthly' => 79.00,
                'price_yearly' => 790.00,
                'max_employees' => 25,
                'max_properties' => 100,
                'features' => ['calendar_sync' => true],
                'is_active' => true,
            ]
        );
    }

    public function test_tenant_admin_can_submit_inquiry_and_super_admin_receives_it(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->tenantAdmin)->post(route('tenant.queries.store'), [
            'title' => 'Need additional cleaner slots',
            'category' => 'quota_upgrade',
            'priority' => 'high',
            'message' => 'Our field crew has expanded to 30 cleaners this week.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_queries', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Need additional cleaner slots',
            'category' => 'quota_upgrade',
            'priority' => 'high',
        ]);

        $query = TenantQuery::first();
        $this->assertNotNull($query);
        $this->assertDatabaseHas('tenant_query_messages', [
            'tenant_query_id' => $query->id,
            'user_id' => $this->tenantAdmin->id,
            'sender_type' => 'tenant_admin',
        ]);
    }

    public function test_two_way_communication_tenant_admin_and_super_admin_replies(): void
    {
        Mail::fake();

        // 1. Super admin opens inquiry directed to tenant
        $this->actingAs($this->superAdmin)->post(route('tenant-manager.queries.store'), [
            'tenant_id' => $this->tenant->id,
            'title' => 'Billing Notice: Trial Expiration Approaching',
            'category' => 'billing',
            'priority' => 'normal',
            'message' => 'Your trial ends in 2 days. Please choose a subscription plan.',
        ]);

        $query = TenantQuery::first();
        $this->assertNotNull($query);
        $this->assertEquals(TenantQuery::STATUS_WAITING_TENANT, $query->status);

        // 2. Tenant Admin replies to the query
        $this->actingAs($this->tenantAdmin)->post(route('tenant.queries.reply', $query->id), [
            'message' => 'Understood! We are currently testing Stripe checkout with our finance team.',
        ]);

        $query->refresh();
        $this->assertEquals(TenantQuery::STATUS_TENANT_RESPONDED, $query->status);
        $this->assertCount(2, $query->messages);

        // 3. Super admin marks status resolved
        $this->actingAs($this->superAdmin)->post(route('tenant-manager.queries.status', $query->id), [
            'status' => 'resolved',
        ]);

        $query->refresh();
        $this->assertEquals(TenantQuery::STATUS_RESOLVED, $query->status);
    }

    public function test_automatic_invoice_generation_and_viewing(): void
    {
        Mail::fake();

        $payment = TenantPayment::create([
            'tenant_id' => $this->tenant->id,
            'package_id' => $this->package->id,
            'amount' => 79.00,
            'billing_cycle' => 'monthly',
            'payment_gateway' => 'stripe',
            'gateway_transaction_id' => 'ch_test_123456789',
            'status' => 'completed',
        ]);

        // Generate and email invoice
        $invoiceService = app(InvoiceService::class);
        $sent = $invoiceService->generateAndSendInvoice($payment, 'billing@acme.test');

        $this->assertTrue($sent);
        $payment->refresh();
        $this->assertNotNull($payment->invoice_number);
        $this->assertStringStartsWith('INV-', $payment->invoice_number);
        $this->assertNotNull($payment->invoice_sent_at);

        Mail::assertQueued(TenantInvoiceMail::class);

        // Tenant Admin can view printable invoice
        $response = $this->actingAs($this->tenantAdmin)->get(route('billing.invoices.show', $payment->id));
        $response->assertOk();
        $response->assertSee($payment->invoice_number);
        $response->assertSee('Acme Cleaners');
    }

    public function test_automated_alerts_dispatch_and_in_app_feed(): void
    {
        Mail::fake();

        $alertService = app(TenantAlertService::class);
        $alert = $alertService->dispatchAlert(
            $this->tenant,
            TenantAlert::TYPE_TRIAL_EXPIRING,
            'Trial Expiring in 2 Days',
            'Your trial evaluation period will conclude in 48 hours.',
            'warning',
            '/admin/billing',
            true
        );

        $this->assertNotNull($alert);
        $this->assertDatabaseHas('tenant_alerts', [
            'tenant_id' => $this->tenant->id,
            'type' => TenantAlert::TYPE_TRIAL_EXPIRING,
            'severity' => 'warning',
            'is_read' => false,
        ]);

        Mail::assertQueued(TenantAlertMail::class);

        // Tenant Admin views alerts list
        $response = $this->actingAs($this->tenantAdmin)->get(route('tenant.alerts.index'));
        $response->assertOk();
        $response->assertSee('Trial Expiring in 2 Days');

        // Tenant Admin marks alert as read
        $this->actingAs($this->tenantAdmin)->post(route('tenant.alerts.read', $alert->id));
        $alert->refresh();
        $this->assertTrue((bool)$alert->is_read);
    }
}
