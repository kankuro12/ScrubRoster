<?php

namespace Tests\Feature;

use App\Jobs\SendTenantPaymentInvoice;
use App\Mail\TenantInvoiceMail;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): TenantPayment
    {
        $tenant = Tenant::createWithSlug('Invoice customer', 'invoice-customer');
        User::factory()->create(['tenant_id' => $tenant->id, 'role' => User::ROLE_ADMIN, 'status' => 'active', 'email' => 'billing@example.test']);
        $coupon = Coupon::create(['code' => 'INVOICE20', 'name' => 'Invoice discount', 'discount_type' => 'percentage', 'discount_value' => 20, 'is_active' => true]);
        return TenantPayment::create(['tenant_id' => $tenant->id, 'package_id' => Package::where('slug', 'growth')->first()->id,
            'coupon_id' => $coupon->id, 'payment_gateway' => 'stripe', 'gateway_transaction_id' => 'in_snapshot',
            'stripe_invoice_id' => 'in_snapshot', 'amount' => 63.20, 'currency' => 'USD', 'billing_cycle' => 'monthly',
            'status' => 'completed', 'metadata' => ['base_price' => 79, 'discount' => 15.80, 'package_name' => 'Agreed Fleet', 'max_employees' => 25, 'coupon_code' => 'INVOICE20']]);
    }

    public function test_invoice_survives_price_and_coupon_changes(): void
    {
        $payment = $this->payment();
        $payment->package->update(['price_monthly' => 999, 'name' => 'Edited plan']);
        $payment->coupon->update(['is_active' => false]);
        $data = app(InvoiceService::class)->formatInvoiceData($payment->fresh());
        $this->assertSame(79.0, $data['base_price']);
        $this->assertSame(15.80, $data['discount']);
        $this->assertSame('Agreed Fleet', $data['package_name']);
        $this->assertSame(63.20, $data['total_amount']);
    }

    public function test_invoice_job_does_not_resend_sent_invoice_and_ignores_stale_tenant_context(): void
    {
        Mail::fake();
        $payment = $this->payment();
        app(TenantContext::class)->setTenant(Tenant::createWithSlug('Unrelated', 'unrelated'));
        $job = new SendTenantPaymentInvoice($payment->id);
        $job->handle(app(InvoiceService::class));
        $job->handle(app(InvoiceService::class));
        Mail::assertSent(TenantInvoiceMail::class, fn ($mail) => $mail->hasTo('billing@example.test'));
        Mail::assertSentCount(1);
        $this->assertNotNull($payment->fresh()->invoice_sent_at);
        $this->assertDatabaseCount('tenant_alerts', 1);
    }

    public function test_email_failure_does_not_undo_paid_invoice(): void
    {
        $payment = $this->payment();
        $service = $this->mock(InvoiceService::class);
        $service->shouldReceive('generateAndSendInvoice')->once()->andReturn(false);
        try {
            (new SendTenantPaymentInvoice($payment->id))->handle($service);
            $this->fail('Expected retryable delivery failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Tenant invoice delivery failed.', $e->getMessage());
        }
        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertDatabaseCount('tenant_payments', 1);
    }
}
