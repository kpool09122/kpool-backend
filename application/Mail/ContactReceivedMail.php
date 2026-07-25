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

    private const array SUBJECTS = [
        'ja' => 'お問い合わせが届きました',
        'en' => 'A New Inquiry Has Been Received',
        'ko' => '새 문의가 도착했습니다',
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
            text: 'emails.contact.received_' . $this->contact->language()->value,
        );
    }
}
