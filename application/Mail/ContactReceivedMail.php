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
            subject: ContactMailCopy::forContact($this->contact)['receivedSubject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.received',
            text: 'emails.contact.text.received',
            with: ['copy' => ContactMailCopy::forContact($this->contact)],
        );
    }
}
