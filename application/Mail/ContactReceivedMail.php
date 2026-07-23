<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\SiteManagement\Contact\Domain\Entity\Contact;

class ContactReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Contact $contact,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'お問い合わせが届きました',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact.received',
        );
    }
}
