<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

class ContactSubmitterReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Contact $contact,
        public readonly ReplyContent $replyContent,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ContactMailCopy::forContact($this->contact)['submitterSubject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.submitter_reply',
            text: 'emails.contact.text.submitter_reply',
            with: ['copy' => ContactMailCopy::forContact($this->contact)],
        );
    }
}
