<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class DelegationApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'デレゲーション申請が承認されました',
        'en' => 'Your delegation request has been approved',
        'ko' => '권한 위임 신청이 승인되었습니다',
    ];

    public function __construct(
        public readonly string $approvalUrl,
        public readonly string $approverAccountName,
        public readonly Language $language,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::SUBJECTS[$this->language->value]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account.delegation_approved_' . $this->language->value,
            text: 'emails.account.text.delegation_approved_' . $this->language->value,
        );
    }
}
