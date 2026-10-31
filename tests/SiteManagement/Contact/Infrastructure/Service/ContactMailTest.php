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
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContactMailTest extends TestCase
{
    public function testReceivedEmailShowsJapaneseCategoriesForEverySubmitterLanguage(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $labels = ['ご意見・ご要望', '不具合のご報告', '掲載内容の修正依頼', 'その他'];
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
                $mail->assertSeeInText($labels[$index]);
                $mail->assertSeeInHtml($labels[$index]);
                $mail->assertSeeInHtml('k-pool');
                $mail->assertSeeInText('k-pool');
                $this->assertNotNull($mail->content()->view);
            }
        }
    }

    public function testNotificationsUseRecipientLanguageAndPreserveLiteralContentSafely(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        config()->set('app.frontend_url', 'https://k-pool.example.com');
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
            $this->assertSame('お問い合わせに本人から返信が届きました', $followup->envelope()->subject);
            foreach ([$received, $followup, $reply] as $mail) {
                $html = $mail->render();
                $this->assertStringContainsString('<!DOCTYPE html>', $html);
                $expectedLocale = $mail instanceof ContactReplyMail ? $language->value : 'ja';
                $this->assertStringContainsString('lang="' . $expectedLocale . '"', $html);
                $this->assertStringNotContainsString('<script>', $html);
                $mail->assertSeeInHtml($body);
                $mail->assertSeeInText($body);
                $mail->assertDontSeeInHtml((string) $contact->contactIdentifier());
                $mail->assertDontSeeInText((string) $contact->contactIdentifier());
            }
            foreach ([$received, $followup] as $mail) {
                $this->assertStringContainsString('href="https://k-pool.example.com/admin/site-management/contact-management"', $mail->render());
                $mail->assertSeeInHtml('Member <tag>&');
                $mail->assertSeeInText('Member <tag>&');
            }
            $reply->assertSeeInHtml('support@example.com');
            $reply->assertDontSeeInHtml('<a ', false);
        }
    }

    public function testDashboardLinksFollowTheRecipientInHtmlAndTextForEveryLanguage(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        foreach (['http://localhost:3000', 'https://k-pool.example.com/'] as $frontendUrl) {
            config()->set('app.frontend_url', $frontendUrl);
            $baseUrl = rtrim($frontendUrl, '/');
            foreach (Language::cases() as $language) {
                $contact = new Contact(
                    new ContactIdentifier(StrTestHelper::generateUuid()),
                    new PrincipalIdentifier(StrTestHelper::generateUuid()),
                    Category::cases()[0],
                    new ContactName('Member'),
                    new Email('member@example.com'),
                    new Content('Inquiry content'),
                    $language,
                );
                $replyContent = new ReplyContent('Follow-up reply');
                $notifications = [
                    [new ContactReceivedMail($contact), '/admin/site-management/contact-management'],
                    [new ContactSubmitterReplyMail($contact, $replyContent), '/admin/site-management/contact-management'],
                    [new ContactReplyMail($contact, $replyContent, new Email('support@example.com')), '/admin/site-management/contacts'],
                ];
                foreach ($notifications as [$mail, $path]) {
                    $url = $baseUrl . $path;
                    $this->assertStringContainsString('href="' . $url . '"', $mail->render());
                    $mail->assertSeeInText($url);
                    $mail->assertDontSeeInHtml((string) $contact->contactIdentifier());
                    $mail->assertDontSeeInText((string) $contact->contactIdentifier());
                }
            }
        }
    }

    public function testReplyNotificationsQuoteThePreviousMessageInsteadOfCategoryInEveryLanguage(): void
    {
        view()->addLocation(dirname(__DIR__, 5) . '/resources/views');
        $labels = [
            'ja' => ['あなたのメッセージ', '今回の返信内容', 'カテゴリ'],
            'en' => ['Your message', 'Latest reply', 'Category'],
            'ko' => ['보내신 메시지', '이번 답변 내용', '카테고리'],
        ];
        foreach (Language::cases() as $language) {
            $contact = new Contact(
                new ContactIdentifier(StrTestHelper::generateUuid()),
                new PrincipalIdentifier(StrTestHelper::generateUuid()),
                Category::SUGGESTIONS,
                new ContactName('Member'),
                new Email('member@example.com'),
                new Content('Original inquiry'),
                $language,
            );
            foreach ([null, 'Latest message <script>alert(1)</script>&'] as $previousMessage) {
                $currentContent = new ReplyContent('Current reply <tag>&');
                $mails = [
                    new ContactReplyMail($contact, $currentContent, new Email('support@example.com'), $previousMessage),
                    new ContactSubmitterReplyMail($contact, $currentContent, $previousMessage),
                ];
                foreach ($mails as $mail) {
                    [$previousLabel, $replyLabel, $categoryLabel] = $labels[$mail instanceof ContactReplyMail ? $language->value : 'ja'];
                    $expectedQuote = $previousMessage ?? (string) $contact->content();
                    $mail->assertSeeInOrderInHtml([$previousLabel, $expectedQuote, $replyLabel, (string) $currentContent]);
                    $mail->assertSeeInOrderInText([$previousLabel, $expectedQuote, $replyLabel, (string) $currentContent]);
                    $mail->assertDontSeeInHtml($categoryLabel);
                    $mail->assertDontSeeInText($categoryLabel);
                    $this->assertStringNotContainsString('<script>', $mail->render());
                }
            }
        }
    }
}
