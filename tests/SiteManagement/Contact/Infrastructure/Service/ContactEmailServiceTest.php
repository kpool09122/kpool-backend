<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Service;

use Application\Mail\ContactAcceptedMail;
use Application\Mail\ContactReceivedMail;
use Application\Mail\ContactReplyMail;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Mail;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Infrastructure\Service\ContactEmailService;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ContactEmailServiceTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $service = $this->app->make(EmailServiceInterface::class);

        $this->assertInstanceOf(ContactEmailService::class, $service);
    }

    /**
     * 正常系: 問い合わせ受付メールがユーザーへ送信されること
     */
    public function testSendContactToUser(): void
    {
        Mail::fake();

        $contact = $this->createContact();

        $service = $this->app->make(EmailServiceInterface::class);
        $service->sendContactToUser($contact);

        Mail::assertSent(ContactAcceptedMail::class, static fn (ContactAcceptedMail $mail): bool => $mail->hasTo((string) $contact->email())
            && $mail->contact === $contact);
    }

    /**
     * 正常系: 問い合わせ通知メールが管理者へ送信されること
     */
    public function testSendContactToAdministrator(): void
    {
        Mail::fake();

        $administratorEmail = new Email('admin@example.com');
        $contact = $this->createContact();
        $service = new ContactEmailService($administratorEmail);

        $service->sendContactToAdministrator($contact);

        Mail::assertSent(ContactReceivedMail::class, static fn (ContactReceivedMail $mail): bool => $mail->hasTo((string) $administratorEmail)
            && $mail->contact === $contact);
    }

    /**
     * 正常系: 問い合わせ返信メールがユーザーへ送信されること
     */
    public function testSendReplyToUser(): void
    {
        Mail::fake();

        $toEmail = new Email('john.doe@example.com');
        $content = new ReplyContent('返信内容');

        $service = $this->app->make(EmailServiceInterface::class);
        $service->sendReplyToUser($toEmail, $content);

        Mail::assertSent(ContactReplyMail::class, static fn (ContactReplyMail $mail): bool => $mail->hasTo((string) $toEmail)
            && $mail->content === $content);
    }

    private function createContact(): Contact
    {
        return new Contact(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容')
        );
    }
}
