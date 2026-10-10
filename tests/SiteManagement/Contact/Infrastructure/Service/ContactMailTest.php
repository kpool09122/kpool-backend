<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Service;

use Application\Mail\ContactReceivedMail;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
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
}
