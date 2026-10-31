<?php

declare(strict_types=1);

namespace Application\Mail;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;

final class ContactMailCopy
{
    private const array COPY = [
        'ja' => [
            'receivedSubject' => 'お問い合わせが届きました',
            'receivedIntro' => '新しいお問い合わせが届きました。以下の内容をご確認ください。',
            'submitterSubject' => 'お問い合わせに本人から返信が届きました',
            'submitterIntro' => 'お問い合わせの送信者から追加の返信が届きました。今回の返信内容をご確認ください。',
            'replySubject' => 'お問い合わせへの返信',
            'replyIntro' => 'k-poolをご利用いただきありがとうございます。お問い合わせいただいた内容について、運営より回答いたします。',
            'nameLabel' => 'お名前', 'emailLabel' => 'メールアドレス', 'categoryLabel' => 'カテゴリ',
            'inquiryLabel' => 'お問い合わせ内容', 'previousMessageLabel' => 'あなたのメッセージ', 'replyLabel' => '今回の返信内容',
            'managementNext' => '管理画面のお問い合わせ一覧から、内容の確認と返信をお願いいたします。',
            'replyNext' => '返信の確認や追加のご質問は、ログイン後、管理画面のお問い合わせ履歴からお願いいたします。',
            'anonymousNext' => '追加のご質問がございましたら、以下のメールアドレスまでお問い合わせください。',
            'openHistory' => 'お問い合わせ履歴を開く', 'openManagement' => 'お問い合わせ一覧を開く',
            'fallback' => 'ボタンが開けない場合は、以下のURLをブラウザにコピーしてください。',
        ],
        'en' => [
            'receivedSubject' => 'A New Contact Message Has Been Received',
            'receivedIntro' => 'A new contact message has arrived. Please review the details below.',
            'submitterSubject' => 'A Follow-up Reply Has Been Received',
            'submitterIntro' => 'The sender has sent a follow-up reply to their contact message. Please review their latest message below.',
            'replySubject' => 'Reply to Your Contact Message',
            'replyIntro' => 'Thank you for using k-pool. Our support team has responded to your message below.',
            'nameLabel' => 'Name', 'emailLabel' => 'Email address', 'categoryLabel' => 'Category',
            'inquiryLabel' => 'Message details', 'previousMessageLabel' => 'Your message', 'replyLabel' => 'Latest reply',
            'managementNext' => 'Open Contact management in the dashboard to review the conversation and send a reply.',
            'replyNext' => 'To view your reply or ask a follow-up question, sign in and open Contact history in the dashboard.',
            'anonymousNext' => 'For follow-up questions, contact us at the email address below.',
            'openHistory' => 'Open Contact history', 'openManagement' => 'Open Contact management',
            'fallback' => 'If the button does not open, copy the URL below into your browser.',
        ],
        'ko' => [
            'receivedSubject' => '새 문의가 도착했습니다',
            'receivedIntro' => '새로운 문의가 도착했습니다. 아래 내용을 확인해 주세요.',
            'submitterSubject' => '문의 작성자의 추가 답변이 도착했습니다',
            'submitterIntro' => '문의 작성자가 추가 답변을 보냈습니다. 아래에서 이번 답변 내용을 확인해 주세요.',
            'replySubject' => '문의에 대한 답변',
            'replyIntro' => 'k-pool을 이용해 주셔서 감사합니다. 문의하신 내용에 대해 운영팀이 아래와 같이 답변드립니다.',
            'nameLabel' => '이름', 'emailLabel' => '이메일 주소', 'categoryLabel' => '카테고리',
            'inquiryLabel' => '문의 내용', 'previousMessageLabel' => '보내신 메시지', 'replyLabel' => '이번 답변 내용',
            'managementNext' => '관리 화면의 문의 목록에서 대화 내용을 확인하고 답변해 주세요.',
            'replyNext' => '답변 확인이나 추가 문의는 로그인 후 관리 화면의 문의 내역에서 해 주세요.',
            'anonymousNext' => '추가 문의는 아래 이메일 주소로 보내 주세요.',
            'openHistory' => '문의 내역 열기', 'openManagement' => '문의 목록 열기',
            'fallback' => '버튼이 열리지 않으면 아래 URL을 복사하여 브라우저에 붙여 넣어 주세요.',
        ],
    ];

    private const array CATEGORIES = [
        'ja' => [1 => 'ご意見・ご要望', 2 => '不具合のご報告', 3 => '掲載内容の修正依頼', 99 => 'その他'],
        'en' => [1 => 'Feedback and suggestions', 2 => 'Bug report', 3 => 'Content correction', 99 => 'Other'],
        'ko' => [1 => '의견 및 제안', 2 => '오류 신고', 3 => '게시 내용 수정 요청', 99 => '기타'],
    ];

    /** @return array<string, string> */
    public static function forContact(Contact $contact, ?Language $language = null): array
    {
        $language ??= $contact->language();
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');

        return self::COPY[$language->value] + [
            'locale' => $language->value,
            'category' => self::CATEGORIES[$language->value][$contact->category()->value],
            'historyUrl' => $frontendUrl . '/admin/site-management/contacts',
            'managementUrl' => $frontendUrl . '/admin/site-management/contact-management',
        ];
    }
}
