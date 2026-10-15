<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class AffiliationApprovedMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'アフィリエーション申請が承認されました',
        'en' => 'Your affiliation request has been approved',
        'ko' => '소속 신청이 승인되었습니다',
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
            view: 'emails.account.affiliation_approved_' . $this->language->value,
            text: 'emails.account.text.affiliation_approved_' . $this->language->value,
        );
    }
}
