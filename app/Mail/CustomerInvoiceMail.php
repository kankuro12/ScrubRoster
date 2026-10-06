<?php

namespace App\Mail;

use App\Models\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly CustomerInvoice $invoice
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->invoice->tenant?->name ?? 'ScrubRoster';
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number} from {$tenantName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-invoice',
            with: [
                'invoice' => $this->invoice,
                'client' => $this->invoice->client,
                'tenant' => $this->invoice->tenant,
                'items' => $this->invoice->items,
            ],
        );
    }
}
