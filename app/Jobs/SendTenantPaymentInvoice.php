<?php

namespace App\Jobs;

use App\Models\TenantPayment;
use App\Services\Billing\InvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SendTenantPaymentInvoice implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function backoff(): array
    {
        return [30, 120];
    }

    public function __construct(public int $paymentId) {}

    public function handle(InvoiceService $invoices): void
    {
        DB::transaction(function () use ($invoices): void {
            $payment = TenantPayment::withoutGlobalScopes()->whereKey($this->paymentId)->lockForUpdate()->firstOrFail();
            if (! $payment->invoice_sent_at && ! $invoices->generateAndSendInvoice($payment)) {
                throw new RuntimeException('Tenant invoice delivery failed.');
            }
        });
    }
}
