<?php

namespace App\Mail;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DocumentRequest $documentRequest,
        public User $requester,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DMC DataBox] Nouvelle demande de document — ' . $this->documentRequest->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.document-request',
            with: [
                'request'   => $this->documentRequest,
                'requester' => $this->requester,
                'adminUrl'  => url(route('admin.document-requests.index', [], false)),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
