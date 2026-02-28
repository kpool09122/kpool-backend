<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Application\UseCase\Command\ReplyContact;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use RuntimeException;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContact;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInput;
use Source\SiteManagement\Contact\Application\UseCase\Command\ReplyContact\ReplyContactInterface;
use Source\SiteManagement\Contact\Application\UseCase\Exception\ContactNotFoundException;
use Source\SiteManagement\Contact\Application\UseCase\Exception\FailedToSendEmailException;
use Source\SiteManagement\Contact\Domain\Entity\Contact;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Repository\ReplyContactRepositoryInterface;
use Source\SiteManagement\Contact\Domain\Service\EmailServiceInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\Category;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactName;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\Content;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ReplyContactTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $emailService = Mockery::mock(EmailServiceInterface::class);
        $this->app->instance(EmailServiceInterface::class, $emailService);

        $replyContact = $this->app->make(ReplyContactInterface::class);
        $this->assertInstanceOf(ReplyContact::class, $replyContact);
    }

    /**
     * 正常系：問い合わせ者へ返信メールを送信し、送信履歴が保存されること.
     *
     * @throws BindingResolutionException
     */
    public function testProcess(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $identityIdentifier,
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容')
        );

        $expectedContent = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $input = new ReplyContactInput(
            $contactIdentifier,
            $identityIdentifier,
            $expectedContent,
        );

        $contactRepository = Mockery::mock(ContactRepositoryInterface::class);
        $contactRepository->shouldReceive('findById')
            ->once()
            ->with($contactIdentifier)
            ->andReturn($contact);

        $emailService = Mockery::mock(EmailServiceInterface::class);
        $emailService->shouldReceive('sendReplyToUser')
            ->once()
            ->withArgs(function (Email $toEmail, ReplyContent $content) use ($contact, $expectedContent): bool {
                return (string)$toEmail === (string)$contact->email()
                    && (string)$content === $expectedContent;
            })
            ->andReturnNull();

        $unsentReply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $identityIdentifier,
            $contact->email(),
            new ReplyContent($expectedContent),
            ReplyStatus::UNSENT,
            null,
            new DateTimeImmutable('2020-01-01 00:00:00'),
        );
        $replyContactFactory = Mockery::mock(ReplyContactFactoryInterface::class);
        $replyContactFactory->shouldReceive('create')
            ->once()
            ->withArgs(function (
                ContactIdentifier $ci,
                ?IdentityIdentifier $ii,
                Email $toEmail,
                ReplyContent $content,
                ReplyStatus $status,
                ?DateTimeImmutable $sentAt,
            ) use ($contactIdentifier, $contact, $expectedContent, $identityIdentifier): bool {
                return $ci === $contactIdentifier
                    && $ii === $identityIdentifier
                    && (string)$toEmail === (string)$contact->email()
                    && (string)$content === $expectedContent
                    && $status === ReplyStatus::UNSENT
                    && $sentAt === null;
            })
            ->andReturn($unsentReply);

        $replyContactRepository = Mockery::mock(ReplyContactRepositoryInterface::class);
        $replyContactRepository->shouldReceive('findById')
            ->once()
            ->with($unsentReply->replyIdentifier())
            ->andReturn($unsentReply);
        $replyContactRepository->shouldReceive('save')
            ->once()
            ->with($unsentReply)
            ->andReturnNull();
        $replyContactRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (ReplyCotact $saved) use ($unsentReply, $contact, $expectedContent, $identityIdentifier): bool {
                return (string)$saved->replyIdentifier() === (string)$unsentReply->replyIdentifier()
                    && (string)$saved->contactIdentifier() === (string)$unsentReply->contactIdentifier()
                    && (string)$saved->identityIdentifier() === (string)$identityIdentifier
                    && (string)$saved->toEmail() === (string)$contact->email()
                    && (string)$saved->content() === $expectedContent
                    && $saved->status() === ReplyStatus::SENT
                    && $saved->sentAt() instanceof DateTimeImmutable
                    && $saved->createdAt() === $unsentReply->createdAt();
            })
            ->andReturnNull();

        $this->app->instance(ContactRepositoryInterface::class, $contactRepository);
        $this->app->instance(ReplyContactFactoryInterface::class, $replyContactFactory);
        $this->app->instance(ReplyContactRepositoryInterface::class, $replyContactRepository);
        $this->app->instance(EmailServiceInterface::class, $emailService);

        $useCase = $this->app->make(ReplyContactInterface::class);
        $useCase->process($input);
    }

    /**
     * 異常系：問い合わせが存在しない場合、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testWhenContactNotFound(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $expectedContent = 'お問い合わせありがとうございます。

内容を確認のうえ、担当より折り返しご連絡いたします。
今しばらくお待ちください。';
        $input = new ReplyContactInput(
            $contactIdentifier,
            $identityIdentifier,
            $expectedContent,
        );

        $contactRepository = Mockery::mock(ContactRepositoryInterface::class);
        $contactRepository->shouldReceive('findById')
            ->once()
            ->with($contactIdentifier)
            ->andReturnNull();

        $emailService = Mockery::mock(EmailServiceInterface::class);
        $emailService->shouldNotReceive('sendReplyToUser');

        $replyContactFactory = Mockery::mock(ReplyContactFactoryInterface::class);
        $replyContactFactory->shouldNotReceive('create');

        $replyContactRepository = Mockery::mock(ReplyContactRepositoryInterface::class);
        $replyContactRepository->shouldNotReceive('save');

        $this->app->instance(ContactRepositoryInterface::class, $contactRepository);
        $this->app->instance(ReplyContactFactoryInterface::class, $replyContactFactory);
        $this->app->instance(ReplyContactRepositoryInterface::class, $replyContactRepository);
        $this->app->instance(EmailServiceInterface::class, $emailService);

        $this->expectException(ContactNotFoundException::class);
        $useCase = $this->app->make(ReplyContactInterface::class);
        $useCase->process($input);
    }

    /**
     * 異常系：メール送信に失敗した場合も、FAILEDとして履歴は保存され、例外がスローされること.
     *
     * @throws BindingResolutionException
     */
    public function testWhenFailedToSendEmail(): void
    {
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $contact = new Contact(
            $contactIdentifier,
            $identityIdentifier,
            Category::SUGGESTIONS,
            new ContactName('お名前'),
            new Email('john.doe@example.com'),
            new Content('お問い合わせ内容')
        );

        $expectedContent = 'お問い合わせありがとうございます。

ご連絡いただいた件につきまして、現在確認を進めております。
恐れ入りますが、回答まで今しばらくお時間をいただけますと幸いです。';
        $input = new ReplyContactInput(
            $contactIdentifier,
            $identityIdentifier,
            $expectedContent,
        );

        $contactRepository = Mockery::mock(ContactRepositoryInterface::class);
        $contactRepository->shouldReceive('findById')
            ->once()
            ->with($contactIdentifier)
            ->andReturn($contact);

        $emailService = Mockery::mock(EmailServiceInterface::class);
        $emailService->shouldReceive('sendReplyToUser')
            ->once()
            ->andThrow(new RuntimeException('send failed'));

        $unsentReply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            $contactIdentifier,
            $identityIdentifier,
            $contact->email(),
            new ReplyContent($expectedContent),
            ReplyStatus::UNSENT,
            null,
            new DateTimeImmutable('2020-01-01 00:00:00'),
        );
        $replyContactFactory = Mockery::mock(ReplyContactFactoryInterface::class);
        $replyContactFactory->shouldReceive('create')
            ->once()
            ->withArgs(function (
                ContactIdentifier $ci,
                ?IdentityIdentifier $ii,
                Email $toEmail,
                ReplyContent $content,
                ReplyStatus $status,
                ?DateTimeImmutable $sentAt,
            ) use ($contactIdentifier, $contact, $expectedContent, $identityIdentifier): bool {
                return $ci === $contactIdentifier
                    && $ii === $identityIdentifier
                    && (string)$toEmail === (string)$contact->email()
                    && (string)$content === $expectedContent
                    && $status === ReplyStatus::UNSENT
                    && $sentAt === null;
            })
            ->andReturn($unsentReply);

        $replyContactRepository = Mockery::mock(ReplyContactRepositoryInterface::class);
        $replyContactRepository->shouldReceive('findById')
            ->once()
            ->with($unsentReply->replyIdentifier())
            ->andReturn($unsentReply);
        $replyContactRepository->shouldReceive('save')
            ->once()
            ->with($unsentReply)
            ->andReturnNull();
        $replyContactRepository->shouldReceive('save')
            ->once()
            ->withArgs(function (ReplyCotact $saved) use ($unsentReply, $contact, $expectedContent, $identityIdentifier): bool {
                return (string)$saved->replyIdentifier() === (string)$unsentReply->replyIdentifier()
                    && (string)$saved->contactIdentifier() === (string)$unsentReply->contactIdentifier()
                    && (string)$saved->identityIdentifier() === (string)$identityIdentifier
                    && (string)$saved->toEmail() === (string)$contact->email()
                    && (string)$saved->content() === $expectedContent
                    && $saved->status() === ReplyStatus::FAILED
                    && $saved->sentAt() === null
                    && $saved->createdAt() === $unsentReply->createdAt();
            })
            ->andReturnNull();

        $this->app->instance(ContactRepositoryInterface::class, $contactRepository);
        $this->app->instance(ReplyContactFactoryInterface::class, $replyContactFactory);
        $this->app->instance(ReplyContactRepositoryInterface::class, $replyContactRepository);
        $this->app->instance(EmailServiceInterface::class, $emailService);

        $this->expectException(FailedToSendEmailException::class);
        $useCase = $this->app->make(ReplyContactInterface::class);
        $useCase->process($input);
    }
}
