<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class AffiliationRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => '所属リクエストの承認依頼',
        'en' => 'Affiliation request approval',
        'ko' => '소속 요청 승인 요청',
    ];

    public function __construct(
        public readonly string $affiliationUrl,
        public readonly Language $language,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->language->value],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliation.request_' . $this->language->value,
        );
    }
}
