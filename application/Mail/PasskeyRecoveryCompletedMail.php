<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class PasskeyRecoveryCompletedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = ['ja' => 'パスキーの復旧が完了しました', 'en' => 'Your passkey recovery is complete', 'ko' => '패스키 복구가 완료되었습니다'];

    public function __construct(public readonly Language $language)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->language->value]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.passkey_recovery.completed_' . $this->language->value);
    }
}
