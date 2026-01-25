<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class CollaboratorDemotedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'コラボレーター資格への変更について',
        'en' => 'Change to Collaborator Status',
        'ko' => '콜라보레이터 자격으로 변경 안내',
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
            view: 'emails.collaborator.demoted_' . $this->language->value,
        );
    }
}
