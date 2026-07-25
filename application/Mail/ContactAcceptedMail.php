<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\SiteManagement\Contact\Domain\Entity\Contact;

class ContactAcceptedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'お問い合わせを受け付けました',
        'en' => 'We Have Received Your Inquiry',
        'ko' => '문의가 접수되었습니다',
    ];

    public function __construct(
        public readonly Contact $contact,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->contact->language()->value],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact.accepted_' . $this->contact->language()->value,
        );
    }
}
