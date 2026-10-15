<?php

declare(strict_types=1);

namespace Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Source\Shared\Domain\ValueObject\Language;

class WikiWorkflowMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    private const array COPY = [
        'ja' => [
            'approved' => ['subject' => 'Wikiが承認されました', 'intro' => '申請したWikiが承認されました。', 'next' => 'まだ公開されていません。'],
            'rejected' => ['subject' => 'Wikiの申請が拒否されました', 'intro' => '申請したWikiは承認されませんでした。', 'next' => '拒否理由を確認し、必要な修正を行ってから再申請できます。'],
            'published' => ['subject' => 'Wikiが公開されました', 'intro' => '申請したWikiが公開されました。', 'next' => '公開された内容を、以下のボタンからご確認いただけます。'],
        ],
        'en' => [
            'approved' => ['subject' => 'Your Wiki has been approved', 'intro' => 'The Wiki you submitted has been approved.', 'next' => 'This Wiki has not been published yet.'],
            'rejected' => ['subject' => 'Your Wiki submission has been rejected', 'intro' => 'The Wiki you submitted was not approved.', 'next' => 'Review the reason below, make any necessary changes, and submit it again.'],
            'published' => ['subject' => 'Your Wiki has been published', 'intro' => 'The Wiki you submitted has been published.', 'next' => 'You can view the published content using the button below.'],
        ],
        'ko' => [
            'approved' => ['subject' => 'Wiki가 승인되었습니다', 'intro' => '신청하신 Wiki가 승인되었습니다.', 'next' => '이 Wiki는 아직 공개되지 않았습니다.'],
            'rejected' => ['subject' => 'Wiki 신청이 거절되었습니다', 'intro' => '신청하신 Wiki가 승인되지 않았습니다.', 'next' => '거절 사유를 확인하고 필요한 내용을 수정한 후 다시 신청할 수 있습니다.'],
            'published' => ['subject' => 'Wiki가 공개되었습니다', 'intro' => '신청하신 Wiki가 공개되었습니다.', 'next' => '아래 버튼에서 공개된 내용을 확인하실 수 있습니다.'],
        ],
    ];

    private const array COMMON = [
        'ja' => ['wikiLabel' => 'Wiki名', 'reasonLabel' => '拒否理由', 'button' => '内容を確認する', 'fallback' => 'ボタンが開けない場合は、以下のURLをブラウザにコピーしてください。'],
        'en' => ['wikiLabel' => 'Wiki', 'reasonLabel' => 'Reason for rejection', 'button' => 'View details', 'fallback' => 'If the button does not open, copy the URL below into your browser.'],
        'ko' => ['wikiLabel' => 'Wiki 이름', 'reasonLabel' => '거절 사유', 'button' => '내용 확인하기', 'fallback' => '버튼이 열리지 않으면 아래 URL을 복사하여 브라우저에 붙여 넣어 주세요.'],
    ];

    public function __construct(
        public readonly WikiWorkflowMailType $type,
        public readonly string $wikiName,
        public readonly ?string $wikiUrl,
        public readonly Language $language,
        public readonly ?string $rejectionReason = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: self::COPY[$this->language->value][$this->type->value]['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wiki.workflow',
            text: 'emails.wiki.text.workflow',
            with: ['copy' => array_merge(self::COMMON[$this->language->value], self::COPY[$this->language->value][$this->type->value])],
        );
    }
}
