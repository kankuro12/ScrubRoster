<?php

namespace App\Commands;

use App\Jobs\SendTenantPaymentInvoice;
use App\Models\TenantPayment;
use Illuminate\Console\Command;

class RetryStripeInvoices extends Command
{
    protected $signature = 'billing:retry-stripe-invoices';

    protected $description = 'Queue delivery for paid Stripe invoices that have not been sent';

    public function handle(): int
    {
        TenantPayment::withoutGlobalScopes()->select('id')
            ->whereNotNull('stripe_invoice_id')->where('status', 'completed')
            ->whereNull('invoice_sent_at')->chunkById(100, function ($payments): void {
                foreach ($payments as $payment) {
                    SendTenantPaymentInvoice::dispatch($payment->id);
                }
            });

        return self::SUCCESS;
    }
}
