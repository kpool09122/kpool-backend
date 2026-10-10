<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\SiteManagement\Contact\Domain\Entity\Contact;

class ContactReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'お問い合わせが届きました',
        'en' => 'A New Inquiry Has Been Received',
        'ko' => '새 문의가 도착했습니다',
    ];

    private const array CATEGORY_LABELS = [
        'ja' => [1 => 'ご意見・ご要望', 2 => '不具合のご報告', 3 => '掲載内容の修正依頼', 99 => 'その他'],
        'en' => [1 => 'Feedback and suggestions', 2 => 'Bug report', 3 => 'Content correction', 99 => 'Other'],
        'ko' => [1 => '의견 및 제안', 2 => '오류 신고', 3 => '게시 내용 수정 요청', 99 => '기타'],
    ];

    public function __construct(
        public readonly Contact $contact,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->contact->language()->value],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact.received_' . $this->contact->language()->value,
            text: 'emails.contact.text.received_' . $this->contact->language()->value,
            with: ['categoryLabel' => self::CATEGORY_LABELS[$this->contact->language()->value][$this->contact->category()->value]],
        );
    }
}
