<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Auth\Domain\Entity\AuthCodeSession;
use Source\Shared\Domain\ValueObject\Language;

class AuthCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => '認証コードのお知らせ',
        'en' => 'Your Verification Code',
        'ko' => '인증 코드 안내',
    ];

    public function __construct(
        public readonly Language $language,
        public readonly AuthCodeSession $session,
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
            view: 'emails.auth.auth_code_' . $this->language->value,
        );
    }
}
