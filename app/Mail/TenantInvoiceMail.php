<?php

namespace App\Mail;

use App\Models\TenantPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TenantPayment $payment,
        public readonly array $invoiceData
    ) {}

    public function envelope(): Envelope
    {
        $invoiceNum = $this->invoiceData['invoice_number'] ?? $this->payment->getInvoiceNumber();

        return new Envelope(
            subject: "Invoice #{$invoiceNum} — ScrubRoster Subscription Receipt",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-invoice',
            with: [
                'payment' => $this->payment,
                'invoice' => $this->invoiceData,
                'tenant' => $this->payment->tenant,
            ],
        );
    }
}
