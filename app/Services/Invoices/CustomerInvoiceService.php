<?php

namespace App\Services\Invoices;

use App\Mail\CustomerInvoiceMail;
use App\Models\Client;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CustomerInvoiceService
{
    /**
     * Create a customer invoice from payload or tasks.
     */
    public function createInvoice(Tenant $tenant, array $data, ?User $user = null): CustomerInvoice
    {
        return DB::transaction(function () use ($tenant, $data, $user) {
            $client = Client::where('id', $data['client_id'])->firstOrFail();

            $invoiceNumber = $data['invoice_number'] ?? CustomerInvoice::generateNextInvoiceNumber($tenant->id);

            $invoice = CustomerInvoice::create([
                'tenant_id' => $tenant->id,
                'client_id' => $client->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(14)->toDateString(),
                'status' => $data['status'] ?? CustomerInvoice::STATUS_DRAFT,
                'tax_rate' => $data['tax_rate'] ?? 0.00,
                'discount_amount' => $data['discount_amount'] ?? 0.00,
                'currency' => $data['currency'] ?? 'USD',
                'recipient_name' => $data['recipient_name'] ?? $client->name,
                'recipient_email' => $data['recipient_email'] ?? $client->email,
                'billing_address' => $data['billing_address'] ?? $client->billing_address,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? 'Payment due within 14 days of invoice issue date.',
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (empty($item['description'])) {
                        continue;
                    }

                    $qty = (float) ($item['quantity'] ?? 1);
                    $price = (float) ($item['unit_price'] ?? 0);
                    $total = round($qty * $price, 2);

                    CustomerInvoiceItem::create([
                        'customer_invoice_id' => $invoice->id,
                        'property_id' => $item['property_id'] ?? null,
                        'task_id' => $item['task_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_price' => $total,
                    ]);
                }
            }

            $invoice->recalculateTotals();

            return $invoice->fresh(['client', 'items']);
        });
    }

    /**
     * Send customer invoice via email.
     */
    public function sendInvoice(CustomerInvoice $invoice, ?string $recipientEmail = null): bool
    {
        $recipient = $recipientEmail ?: $invoice->recipient_email ?: $invoice->client?->email;

        if (! $recipient) {
            return false;
        }

        try {
            Mail::to($recipient)->send(new CustomerInvoiceMail($invoice));

            $invoice->update([
                'status' => $invoice->status === CustomerInvoice::STATUS_DRAFT ? CustomerInvoice::STATUS_SENT : $invoice->status,
                'sent_at' => now(),
                'recipient_email' => $recipient,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to email customer invoice #{$invoice->invoice_number} to {$recipient}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark customer invoice as paid.
     */
    public function markAsPaid(CustomerInvoice $invoice): void
    {
        $invoice->update([
            'status' => CustomerInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
