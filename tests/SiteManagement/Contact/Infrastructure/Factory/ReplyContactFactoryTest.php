<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Contact\Domain\Factory\ReplyContactFactoryInterface;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ReplyContactFactoryTest extends TestCase
{
    /**
     * 正常系: ReplyContact(Entity) が正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $replyIdentifier = StrTestHelper::generateUuid();
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $content = new ReplyContent('返信本文');
        $status = ReplyStatus::SENT;
        $sentAt = new DateTimeImmutable('2026-01-01 12:34:56');

        $generator = Mockery::mock(UuidGeneratorInterface::class);
        $generator->shouldReceive('generate')
            ->once()
            ->andReturn($replyIdentifier);

        $this->app->instance(UuidGeneratorInterface::class, $generator);
        $factory = $this->app->make(ReplyContactFactoryInterface::class);

        $before = new DateTimeImmutable('now');
        $reply = $factory->create(
            $contactIdentifier,
            $identityIdentifier,
            $toEmail,
            $content,
            $status,
            $sentAt,
        );
        $after = new DateTimeImmutable('now');

        $this->assertSame($replyIdentifier, (string)$reply->replyIdentifier());
        $this->assertSame($contactIdentifier, $reply->contactIdentifier());
        $this->assertSame($identityIdentifier, $reply->identityIdentifier());
        $this->assertSame($toEmail, $reply->toEmail());
        $this->assertSame($content, $reply->content());
        $this->assertSame($status, $reply->status());
        $this->assertSame($sentAt, $reply->sentAt());

        // createdAt は factory 内部で "now" が入るため、範囲で検証する
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $reply->createdAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $reply->createdAt()->getTimestamp());
    }

    /**
     * 正常系: sentAt が null の場合も作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithNullSentAt(): void
    {
        $replyIdentifier = StrTestHelper::generateUuid();

        $generator = Mockery::mock(UuidGeneratorInterface::class);
        $generator->shouldReceive('generate')
            ->once()
            ->andReturn($replyIdentifier);

        $this->app->instance(UuidGeneratorInterface::class, $generator);
        $factory = $this->app->make(ReplyContactFactoryInterface::class);

        $reply = $factory->create(
            new ContactIdentifier(StrTestHelper::generateUuid()),
            new IdentityIdentifier(StrTestHelper::generateUuid()),
            new Email('john.doe@example.com'),
            new ReplyContent('返信本文'),
            ReplyStatus::FAILED,
            null,
        );

        $this->assertSame($replyIdentifier, (string)$reply->replyIdentifier());
        $this->assertNull($reply->sentAt());
        $this->assertSame(ReplyStatus::FAILED, $reply->status());
    }
}
