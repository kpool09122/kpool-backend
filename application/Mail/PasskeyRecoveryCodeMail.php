<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class PasskeyRecoveryCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = ['ja' => 'パスキー復旧コード', 'en' => 'Passkey recovery code', 'ko' => '패스키 복구 코드'];

    public function __construct(public readonly Language $language, public readonly string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->language->value]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.passkey_recovery.code_' . $this->language->value);
    }
}
