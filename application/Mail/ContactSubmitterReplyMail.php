<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

class ContactSubmitterReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public readonly string $previousMessage;

    public function __construct(
        public readonly Contact $contact,
        public readonly ReplyContent $replyContent,
        ?string $previousMessage = null,
    ) {
        $this->previousMessage = $previousMessage ?? (string) $contact->content();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ContactMailCopy::forContact($this->contact, Language::JAPANESE)['submitterSubject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.submitter_reply',
            text: 'emails.contact.text.submitter_reply',
            with: [
                'copy' => ContactMailCopy::forContact($this->contact, Language::JAPANESE),
            ],
        );
    }
}
