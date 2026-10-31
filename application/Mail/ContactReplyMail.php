<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

class ContactReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public readonly string $previousMessage;

    public function __construct(
        public readonly Contact $contact,
        public readonly ReplyContent $content,
        public readonly Email $replyToAddress,
        ?string $previousMessage = null,
    ) {
        $this->previousMessage = $previousMessage ?? (string) $contact->content();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ContactMailCopy::forContact($this->contact)['replySubject'],
            replyTo: $this->contact->principalIdentifier() === null
                ? [new Address((string) $this->replyToAddress)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.reply',
            text: 'emails.contact.text.reply',
            with: [
                'copy' => ContactMailCopy::forContact($this->contact),
            ],
        );
    }
}
