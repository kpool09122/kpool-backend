<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class ConflictNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => '会員登録について',
        'en' => 'About Your Registration',
        'ko' => '회원가입 안내',
    ];

    public function __construct(
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
            view: 'emails.auth.conflict_' . $this->language->value,
        );
    }
}
