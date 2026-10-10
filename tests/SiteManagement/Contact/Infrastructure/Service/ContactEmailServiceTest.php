<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Service;

use Application\Mail\ContactAcceptedMail;
use Application\Mail\ContactReceivedMail;
use Application\Mail\ContactReplyMail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Mail;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Infrastructure\Service\ContactEmailService;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContactEmailServiceTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app()['view']->addLocation(dirname(__DIR__, 5) . '/resources/views');
    }

    /**
     * @return array<string, array{Language, string, string, string, string, string}>
     */
    public static function languageProvider(): array
    {
        return [
            '日本語' => [
                Language::JAPANESE,
                'お問い合わせを受け付けました',
                'お問い合わせありがとうございます。',
                'お問い合わせが届きました',
                '名前:',
                'お問い合わせへの返信',
            ],
            '英語' => [
                Language::ENGLISH,
                'We Have Received Your Inquiry',
                'Thank you for contacting us.',
                'A New Inquiry Has Been Received',
                'Name:',
                'Reply to Your Inquiry',
            ],
            '韓国語' => [
                Language::KOREAN,
                '문의가 접수되었습니다',
                '문의해 주셔서 감사합니다.',
                '새 문의가 도착했습니다',
                '이름:',
                '문의에 대한 답변',
            ],
        ];
    }

    /**
     * 正常系: DIが正しく動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app()->make(ContactEmailServiceInterface::class);

        $this->assertInstanceOf(ContactEmailService::class, $service);
    }

    /**
     * 正常系: 問い合わせ受付メールがユーザーへ送信されること
     */
    #[DataProvider('languageProvider')]
    public function testSendContactToUser(
        Language $language,
        string $expectedSubject,
        string $expectedBody,
        string $_receivedSubject,
        string $_receivedBody,
        string $_replySubject,
    ): void {
        Mail::fake();

        $contact = $this->createContact($language);
        $view = 'emails.contact.accepted_' . $language->value;
        $textView = 'emails.contact.text.accepted_' . $language->value;
        $mail = new ContactAcceptedMail($contact);
        $mail->assertSeeInHtml($expectedBody);
        $mail->assertSeeInText($expectedBody);

        $service = $this->app()->make(ContactEmailServiceInterface::class);
        $service->sendContactToUser($contact);

        Mail::assertSent(ContactAcceptedMail::class, static fn (ContactAcceptedMail $mail): bool => $mail->hasTo((string) $contact->email())
            && $mail->contact === $contact
            && $mail->contact->language() === $language
            && $mail->envelope()->subject === $expectedSubject
            && $mail->content()->view === $view
            && $mail->content()->text === $textView);
    }

    /**
     * 正常系: 問い合わせ通知メールが管理者へ送信されること
     */
    #[DataProvider('languageProvider')]
    public function testSendContactToAdministrator(
        Language $language,
        string $_acceptedSubject,
        string $_acceptedBody,
        string $expectedSubject,
        string $expectedBody,
        string $_replySubject,
    ): void {
        Mail::fake();

        $administratorEmail = new Email('admin@example.com');
        $contact = $this->createContact($language);
        $view = 'emails.contact.received_' . $language->value;
        $textView = 'emails.contact.text.received_' . $language->value;
        $mail = new ContactReceivedMail($contact);
        $mail->assertSeeInHtml((string) $contact->name());
        $mail->assertSeeInText($expectedBody);
        $service = new ContactEmailService($administratorEmail);

        $service->sendContactToAdministrator($contact);

        Mail::assertSent(ContactReceivedMail::class, static fn (ContactReceivedMail $mail): bool => $mail->hasTo((string) $administratorEmail)
            && $mail->contact === $contact
            && $mail->contact->language() === $language
            && $mail->envelope()->subject === $expectedSubject
            && $mail->content()->view === $view
            && $mail->content()->text === $textView);
    }

    /**
     * 正常系: 問い合わせ返信メールがユーザーへ送信されること
     */
    #[DataProvider('languageProvider')]
    public function testSendReplyToUser(
        Language $language,
        string $_acceptedSubject,
        string $_acceptedBody,
        string $_receivedSubject,
        string $_receivedBody,
        string $expectedSubject,
    ): void {
        Mail::fake();

        $content = new ReplyContent('返信内容');
        $contact = $this->createContact($language);
        $view = 'emails.contact.reply_' . $language->value;
        $textView = 'emails.contact.text.reply_' . $language->value;
        $mail = new ContactReplyMail($content, $language);
        $mail->assertSeeInHtml((string) $content);
        $mail->assertSeeInText((string) $content);

        $service = $this->app()->make(ContactEmailServiceInterface::class);
        $service->sendReplyToUser($contact, $content);

        Mail::assertSent(ContactReplyMail::class, static fn (ContactReplyMail $mail): bool => $mail->hasTo((string) $contact->email())
            && $mail->content === $content
            && $mail->language === $language
            && $mail->envelope()->subject === $expectedSubject
            && $mail->content()->view === $view
            && $mail->content()->text === $textView);
    }

    private function createContact(Language $language): Contact
    {
        return new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容'),
            $language,
        );
    }
}
