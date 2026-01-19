<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Account\Invitation\Domain\Entity\Invitation;
use Source\Shared\Domain\ValueObject\Language;

class InvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => '%sへの招待',
        'en' => 'Invitation to %s',
        'ko' => '%s 초대',
    ];

    public function __construct(
        public readonly Invitation $invitation,
        public readonly string $invitationUrl,
        public readonly string $accountName,
        public readonly Language $language,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(self::SUBJECTS[$this->language->value], $this->accountName),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation.invitation_' . $this->language->value,
        );
    }
}
