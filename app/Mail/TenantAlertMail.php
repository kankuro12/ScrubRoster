<?php

namespace App\Mail;

use App\Models\TenantAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TenantAlert $alert
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->alert->tenant?->name ?? 'Your Organization';

        return new Envelope(
            subject: "[ScrubRoster Alert] {$this->alert->title} — {$tenantName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-alert',
            with: [
                'alert' => $this->alert,
                'tenant' => $this->alert->tenant,
            ],
        );
    }
}
