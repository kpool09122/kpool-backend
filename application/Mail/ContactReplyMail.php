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

    private const array SUBJECTS = [
        'ja' => 'お問い合わせへの返信',
        'en' => 'Reply to Your Inquiry',
        'ko' => '문의에 대한 답변',
    ];

    public function __construct(
        public readonly Contact $contact,
        public readonly ReplyContent $content,
        public readonly Email $replyToAddress,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->contact->language()->value],
            replyTo: $this->contact->principalIdentifier() === null
                ? [new Address((string) $this->replyToAddress)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.reply_' . $this->contact->language()->value,
            text: 'emails.contact.text.reply_' . $this->contact->language()->value,
        );
    }
}
