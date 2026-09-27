<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class SocialLinkingCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = ['ja' => 'SSO連携の確認コード', 'en' => 'SSO linking verification code', 'ko' => 'SSO 연결 인증 코드'];

    public function __construct(public readonly Language $language, public readonly string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->language->value]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.social_linking.code_' . $this->language->value);
    }
}
