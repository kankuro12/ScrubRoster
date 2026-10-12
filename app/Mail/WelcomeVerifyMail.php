<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class WelcomeVerifyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?Tenant $tenant = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ScrubRoster — verify your email address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-verify',
            with: [
                'user' => $this->user,
                'tenant' => $this->tenant,
                'verificationUrl' => URL::temporarySignedRoute(
                    'verification.verify',
                    now()->addDays(30),
                    ['id' => $this->user->id, 'hash' => sha1($this->user->email)],
                ),
            ],
        );
    }
}
