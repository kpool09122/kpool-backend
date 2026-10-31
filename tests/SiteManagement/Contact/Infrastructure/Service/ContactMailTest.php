<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Service;

use Application\Mail\ContactReceivedMail;
use Application\Mail\ContactReplyMail;
use Application\Mail\ContactSubmitterReplyMail;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContactMailTest extends TestCase
{
    public function testReceivedEmailShowsCategoryNamesInHtmlAndTextForEveryLanguage(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $labels = [
            'ja' => ['ご意見・ご要望', '不具合のご報告', '掲載内容の修正依頼', 'その他'],
            'en' => ['Feedback and suggestions', 'Bug report', 'Content correction', 'Other'],
            'ko' => ['의견 및 제안', '오류 신고', '게시 내용 수정 요청', '기타'],
        ];
        foreach (Language::cases() as $language) {
            foreach (Category::cases() as $index => $category) {
                $contact = new Contact(
                    new ContactIdentifier(StrTestHelper::generateUuid()),
                    null,
                    $category,
                    new ContactName('Member'),
                    new Email('member@example.com'),
                    new Content('Inquiry content'),
                    $language,
                );
                $mail = new ContactReceivedMail($contact);
                $mail->assertSeeInText($labels[$language->value][$index]);
                $mail->assertSeeInHtml($labels[$language->value][$index]);
                $mail->assertSeeInHtml('k-pool');
                $mail->assertSeeInText('k-pool');
                $this->assertNotNull($mail->content()->view);
            }
        }
    }

    public function testEveryNotificationRendersLocalizedHtmlAndLiteralTextWithSafeContent(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        config()->set('app.frontend_url', 'https://k-pool.example.com');
        $subjects = [
            'ja' => 'お問い合わせに本人から返信が届きました',
            'en' => 'A Follow-up Reply Has Been Received',
            'ko' => '문의 작성자의 추가 답변이 도착했습니다',
        ];
        $body = "Message <script>alert(1)</script>& \"quoted\"\nNext line";
        foreach (Language::cases() as $language) {
            $contact = new Contact(
                new ContactIdentifier(StrTestHelper::generateUuid()),
                null,
                Category::cases()[0],
                new ContactName('Member <tag>&'),
                new Email('member@example.com'),
                new Content($body),
                $language,
            );
            $received = new ContactReceivedMail($contact);
            $followup = new ContactSubmitterReplyMail($contact, new ReplyContent($body));
            $reply = new ContactReplyMail($contact, new ReplyContent($body), new Email('support@example.com'));
            $this->assertSame($subjects[$language->value], $followup->envelope()->subject);
            foreach ([$received, $followup, $reply] as $mail) {
                $html = $mail->render();
                $this->assertStringContainsString('<!DOCTYPE html>', $html);
                $this->assertStringContainsString('lang="' . $language->value . '"', $html);
                $this->assertStringNotContainsString('<script>', $html);
                $mail->assertSeeInHtml($body);
                $mail->assertSeeInText($body);
                $mail->assertSeeInHtml((string) $contact->contactIdentifier());
                $mail->assertSeeInText((string) $contact->contactIdentifier());
            }
            foreach ([$received, $followup] as $mail) {
                $this->assertStringContainsString('href="https://k-pool.example.com"', $mail->render());
                $mail->assertSeeInHtml('Member <tag>&');
                $mail->assertSeeInText('Member <tag>&');
            }
            $this->assertStringContainsString('href="mailto:support@example.com"', $reply->render());
        }
    }
}
