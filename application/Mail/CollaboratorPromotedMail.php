<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class CollaboratorPromotedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'シニアコラボレーターに昇格しました',
        'en' => 'You have been promoted to Senior Collaborator',
        'ko' => '시니어 콜라보레이터로 승격되었습니다',
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
            view: 'emails.collaborator.promoted_' . $this->language->value,
        );
    }
}
