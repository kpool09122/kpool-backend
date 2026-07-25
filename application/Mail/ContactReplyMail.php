<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;

class ContactReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array SUBJECTS = [
        'ja' => 'お問い合わせへの返信',
        'en' => 'Reply to Your Inquiry',
        'ko' => '문의에 대한 답변',
    ];

    public function __construct(
        public readonly ReplyContent $content,
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
            text: 'emails.contact.reply_' . $this->language->value,
        );
    }
}
