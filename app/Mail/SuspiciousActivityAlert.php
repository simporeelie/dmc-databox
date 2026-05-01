<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SuspiciousActivityAlert extends Mailable
{
    public function __construct(
        public readonly string $type,
        public readonly string $detail,
        public readonly string $ipAddress,
        public readonly string $occurredAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DMC DataBox] Alerte de sécurité — ' . $this->type,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.suspicious-activity',
        );
    }
}
