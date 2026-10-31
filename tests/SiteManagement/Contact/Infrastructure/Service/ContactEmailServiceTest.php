<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Service;

use Application\Mail\ContactReceivedMail;
use Application\Mail\ContactReplyMail;
use Application\Mail\ContactSubmitterReplyMail;
use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Entity\Reply;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\ContactEmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
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
        $replyContactRepository = Mockery::mock(ReplyContactRepositoryInterface::class);
        $replyContactRepository->shouldReceive('findLatestFromSubmitter', 'findLatestFromOperator')->andReturnNull()->byDefault();
        $this->app()->instance(ReplyContactRepositoryInterface::class, $replyContactRepository);

        $this->app()['view']->addLocation(dirname(__DIR__, 5) . '/resources/views');
    }

    /**
     * @return array<string, array{Language, string, string, string}>
     */
    public static function languageProvider(): array
    {
        return [
            '日本語' => [
                Language::JAPANESE,
                'お問い合わせが届きました',
                '名前:',
                'お問い合わせへの返信',
            ],
            '英語' => [
                Language::ENGLISH,
                'お問い合わせが届きました',
                '名前:',
                'Reply to Your Contact Message',
            ],
            '韓国語' => [
                Language::KOREAN,
                'お問い合わせが届きました',
                '名前:',
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
     * 正常系: 問い合わせ通知メールが管理者へ送信されること
     */
    #[DataProvider('languageProvider')]
    public function testSendContactToAdministrator(
        Language $language,
        string $expectedSubject,
        string $expectedBody,
        string $_replySubject,
    ): void {
        Mail::fake();

        $administratorEmail = new Email('admin@example.com');
        $contact = $this->createContact($language);
        $view = 'emails.contact.received';
        $textView = 'emails.contact.text.received';
        $mail = new ContactReceivedMail($contact);
        $mail->assertSeeInHtml((string) $contact->name());
        $mail->assertSeeInText($expectedBody);
        $service = new ContactEmailService($administratorEmail, new Email('support@example.com'), $this->app()->make(ReplyContactRepositoryInterface::class));

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
        string $_receivedSubject,
        string $_receivedBody,
        string $expectedSubject,
    ): void {
        Mail::fake();

        $content = new ReplyContent('返信内容');
        $contact = $this->createContact($language);
        $view = 'emails.contact.reply';
        $textView = 'emails.contact.text.reply';
        $mail = new ContactReplyMail($contact, $content, new Email('admin@example.com'));
        $mail->assertSeeInHtml((string) $content);
        $mail->assertSeeInText((string) $content);

        $service = $this->app()->make(ContactEmailServiceInterface::class);
        $service->sendReplyToUser($contact, $content);

        Mail::assertSent(ContactReplyMail::class, static fn (ContactReplyMail $mail): bool => $mail->hasTo((string) $contact->email())
            && $mail->content === $content
            && $mail->contact->language() === $language
            && $mail->envelope()->subject === $expectedSubject
            && $mail->content()->view === $view
            && $mail->content()->text === $textView);
    }

    /** @return array<string, array{Language, bool, string}> */
    public static function replyGuidance(): array
    {
        return [
            'Japanese signed in' => [Language::JAPANESE, true, '管理画面のお問い合わせ履歴'],
            'Japanese anonymous' => [Language::JAPANESE, false, '追加のご質問がございましたら'],
            'English signed in' => [Language::ENGLISH, true, 'Contact history in the dashboard'],
            'English anonymous' => [Language::ENGLISH, false, 'For follow-up questions'],
            'Korean signed in' => [Language::KOREAN, true, '관리 화면의 문의 내역'],
            'Korean anonymous' => [Language::KOREAN, false, '추가 문의는'],
        ];
    }

    #[DataProvider('replyGuidance')]
    public function testReplyGuidanceAndReplyToFollowContactOwnership(Language $language, bool $signedIn, string $guidance): void
    {
        Mail::fake();
        $address = new Email('support@example.com');
        $contact = $this->createContact($language, $signedIn);
        $content = new ReplyContent('返信内容 <tag>& "引用"');
        $mail = new ContactReplyMail($contact, $content, $address);
        $mail->assertSeeInHtml($guidance);
        $mail->assertSeeInText($guidance);
        $mail->assertSeeInHtml((string) $content);
        $mail->assertSeeInText((string) $content);
        if ($signedIn) {
            $mail->assertDontSeeInHtml('mailto:');
            $mail->assertDontSeeInText((string) $address);
            $this->assertSame([], $mail->envelope()->replyTo);
        } else {
            $mail->assertSeeInHtml((string) $address);
            $mail->assertDontSeeInHtml('mailto:');
            $mail->assertDontSeeInHtml('<a ', false);
            $mail->assertSeeInText((string) $address);
            $replyTo = $mail->envelope()->replyTo;
            $this->assertCount(1, $replyTo);
            $this->assertInstanceOf(Address::class, $replyTo[0]);
            $this->assertSame((string) $address, $replyTo[0]->address);
        }
        $service = new ContactEmailService(new Email('notifications@example.com'), $address, $this->app()->make(ReplyContactRepositoryInterface::class));
        $service->sendReplyToUser($contact, $content);
        Mail::assertSent(ContactReplyMail::class, static fn (ContactReplyMail $sent): bool => $sent->contact === $contact
            && $sent->hasTo((string) $contact->email())
            && (string) $sent->replyToAddress === (string) $address);
    }

    public function testReplyToUserQuotesTheLatestSubmitterMessage(): void
    {
        Mail::fake();
        $contact = $this->createContact(Language::JAPANESE);
        $previousReply = $this->createPreviousReply($contact, $contact->principalIdentifier());
        $replyContactRepository = Mockery::mock(ReplyContactRepositoryInterface::class);
        $replyContactRepository->shouldReceive('findLatestFromSubmitter')->once()
            ->with($contact->contactIdentifier(), $contact->principalIdentifier())->andReturn($previousReply);
        $this->app()->instance(ReplyContactRepositoryInterface::class, $replyContactRepository);
        $service = $this->app()->make(ContactEmailServiceInterface::class);

        $service->sendReplyToUser($contact, new ReplyContent('Current operator response'));

        Mail::assertSent(ContactReplyMail::class, static fn (ContactReplyMail $mail): bool =>
            $mail->previousMessage === (string) $previousReply->content()
            && (string) $mail->content === 'Current operator response');
    }

    public function testReplyToAdministratorQuotesTheLatestOperatorMessage(): void
    {
        Mail::fake();
        $contact = $this->createContact(Language::JAPANESE);
        $previousReply = $this->createPreviousReply($contact, null);
        $replyContactRepository = Mockery::mock(ReplyContactRepositoryInterface::class);
        $replyContactRepository->shouldReceive('findLatestFromOperator')->once()
            ->with($contact->contactIdentifier(), $contact->principalIdentifier())->andReturn($previousReply);
        $this->app()->instance(ReplyContactRepositoryInterface::class, $replyContactRepository);
        $service = $this->app()->make(ContactEmailServiceInterface::class);

        $service->sendReplyToAdministrator($contact, new ReplyContent('Current submitter response'));

        Mail::assertSent(ContactSubmitterReplyMail::class, static fn (ContactSubmitterReplyMail $mail): bool =>
            $mail->previousMessage === (string) $previousReply->content()
            && (string) $mail->replyContent === 'Current submitter response');
    }

    private function createPreviousReply(Contact $contact, ?PrincipalIdentifier $principalIdentifier): Reply
    {
        return new Reply(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contact->contactIdentifier(),
            $principalIdentifier,
            $contact->email(),
            new ReplyContent('Latest message from the other participant'),
            null,
            null,
            new DateTimeImmutable('2026-01-01'),
            3,
        );
    }

    private function createContact(Language $language, bool $signedIn = true): Contact
    {
        return new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            $signedIn ? new PrincipalIdentifier(StrTestHelper::generateUuid()) : null,
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容'),
            $language,
        );
    }
}
