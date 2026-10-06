<?php

namespace App\Mail;

use App\Models\TenantQuery;
use App\Models\TenantQueryMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantQueryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly TenantQuery $query,
        public readonly ?TenantQueryMessage $messageObj = null,
        public readonly string $recipientType = 'tenant_admin' // 'tenant_admin' or 'platform_admin'
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->recipientType === 'tenant_admin'
            ? '[Platform Inquiry]'
            : '[Tenant Response]';

        return new Envelope(
            subject: "{$prefix} {$this->query->title} (#QUERY-{$this->query->id})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-query',
            with: [
                'query' => $this->query,
                'msg' => $this->messageObj,
                'tenant' => $this->query->tenant,
                'recipientType' => $this->recipientType,
            ],
        );
    }
}
