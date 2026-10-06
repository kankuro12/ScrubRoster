<?php

namespace App\Services\Billing;

use App\Mail\TenantInvoiceMail;
use App\Models\Tenant;
use App\Models\TenantAlert;
use App\Models\TenantPayment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceService
{
    /**
     * Format full invoice data payload for display, PDF, or email.
     */
    public function formatInvoiceData(TenantPayment $payment): array
    {
        $tenant = $payment->tenant;
        $package = $payment->package;
        $coupon = $payment->coupon;

        $basePrice = $payment->billing_cycle === 'yearly'
            ? ($package ? (float) $package->price_yearly : (float) $payment->amount)
            : ($package ? (float) $package->price_monthly : (float) $payment->amount);

        $discount = $coupon ? $coupon->calculateDiscount($basePrice) : 0.0;
        $invoiceNumber = $payment->getInvoiceNumber();

        return [
            'invoice_number' => $invoiceNumber,
            'date' => $payment->created_at ? $payment->created_at->format('M j, Y') : now()->format('M j, Y'),
            'due_date' => $payment->created_at ? $payment->created_at->format('M j, Y') : now()->format('M j, Y'),
            'organization_name' => $tenant?->name ?? 'Organization',
            'organization_slug' => $tenant?->slug ?? 'default',
            'package_name' => $package?->name ?? 'Commercial Fleet Tier',
            'billing_cycle' => ucfirst($payment->billing_cycle),
            'max_employees' => $package?->max_employees ?? 0,
            'base_price' => $basePrice,
            'discount' => $discount,
            'coupon_code' => $coupon?->code,
            'total_amount' => (float) $payment->amount,
            'currency' => $payment->currency ?: 'USD',
            'payment_gateway' => ucfirst($payment->payment_gateway),
            'transaction_id' => $payment->gateway_transaction_id,
            'status' => strtoupper($payment->status),
        ];
    }

    /**
     * Auto generate and send invoice email to tenant admin(s).
     */
    public function generateAndSendInvoice(TenantPayment $payment, ?string $recipientEmail = null): bool
    {
        $tenant = $payment->tenant;
        if (! $tenant) {
            return false;
        }

        // Ensure invoice number is set on record
        if (! $payment->invoice_number) {
            $payment->invoice_number = $payment->getInvoiceNumber();
            $payment->saveQuietly();
        }

        $invoiceData = $this->formatInvoiceData($payment);

        // Resolve recipient email
        $recipient = $recipientEmail;
        if (! $recipient) {
            $adminUser = $tenant->admins()->where('status', 'active')->first()
                ?? $tenant->users()->where('status', 'active')->first();
            $recipient = $adminUser?->email;
        }

        if (! $recipient) {
            Log::warning("Invoice #{$payment->invoice_number} could not be sent: no active admin email found for tenant {$tenant->id}");
            return false;
        }

        try {
            Mail::to($recipient)->send(new TenantInvoiceMail($payment, $invoiceData));

            $payment->update([
                'invoice_sent_at' => now(),
                'invoice_recipient_email' => $recipient,
            ]);

            // Create notification alert for tenant
            TenantAlert::create([
                'tenant_id' => $tenant->id,
                'type' => TenantAlert::TYPE_PAYMENT_SUCCEEDED,
                'severity' => TenantAlert::SEVERITY_SUCCESS,
                'title' => "Invoice #{$payment->invoice_number} Generated",
                'message' => "Payment of \${$payment->amount} for {$invoiceData['package_name']} ({$invoiceData['billing_cycle']}) processed successfully. Invoice emailed to {$recipient}.",
                'action_url' => route('billing.invoices.show', $payment),
                'action_label' => 'View Invoice',
                'email_sent_at' => now(),
                'email_recipient' => $recipient,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed sending invoice email to {$recipient}: " . $e->getMessage());
            return false;
        }
    }
}
