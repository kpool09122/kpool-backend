<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\Language;

class AccountCategoryChangeApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'アカウントのカテゴリ変更申請が承認されました',
        'en' => 'Your account category change has been approved',
        'ko' => '계정 카테고리 변경 신청이 승인되었습니다',
    ];

    private const array CATEGORY_LABELS = [
        'ja' => ['agency' => '事務所', 'talent' => 'タレント', 'general' => '一般'],
        'en' => ['agency' => 'Agency', 'talent' => 'Talent', 'general' => 'General'],
        'ko' => ['agency' => '소속사', 'talent' => '탤런트', 'general' => '일반'],
    ];

    public function __construct(
        public readonly string $categoryChangeUrl,
        public readonly AccountCategory $category,
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
            view: 'emails.account.category_change_approved_' . $this->language->value,
            text: 'emails.account.text.category_change_approved_' . $this->language->value,
            with: ['categoryLabel' => self::CATEGORY_LABELS[$this->language->value][$this->category->value]],
        );
    }
}
