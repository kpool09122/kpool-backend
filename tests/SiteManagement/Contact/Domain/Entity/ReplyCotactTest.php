<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\Entity;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Email;
use Source\SiteManagement\Contact\Domain\Entity\ReplyCotact;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyContent;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ReplyCotactTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $replyIdentifier = new ContactReplyIdentifier(StrTestHelper::generateUuid());
        $contactIdentifier = new ContactIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $toEmail = new Email('john.doe@example.com');
        $contentText = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $content = new ReplyContent($contentText);
        $sentAt = new DateTimeImmutable('2026-01-01 12:34:56');
        $failedAt = null;
        $createdAt = new DateTimeImmutable('2026-01-01 12:34:57');

        $reply = new ReplyCotact(
            $replyIdentifier,
            $contactIdentifier,
            $principalIdentifier,
            $toEmail,
            $content,
            $sentAt,
            $failedAt,
            $createdAt,
        );

        $this->assertSame((string)$replyIdentifier, (string)$reply->replyIdentifier());
        $this->assertSame((string)$contactIdentifier, (string)$reply->contactIdentifier());
        $this->assertSame((string)$principalIdentifier, (string)$reply->principalIdentifier());
        $this->assertSame((string)$toEmail, (string)$reply->toEmail());
        $this->assertSame((string)$content, (string)$reply->content());
        $this->assertSame($sentAt, $reply->sentAt());
        $this->assertSame($failedAt, $reply->failedAt());
        $this->assertSame($createdAt, $reply->createdAt());
    }

    /**
     * 正常系: failedAt が null でなくても許容すること
     */
    public function testFailedAtCanBeSet(): void
    {
        $contentText = 'お問い合わせありがとうございます。

ご要望いただいた「公式MV一覧をYouTube連携で表示する機能」について、今後の改善候補として検討いたします。
実装可否や時期が決まり次第、サイト内のお知らせ等でご案内いたします。

貴重なご意見をお寄せいただき、ありがとうございました。';
        $reply = new ReplyCotact(
            new ContactReplyIdentifier(StrTestHelper::generateUuid()),
            new ContactIdentifier(StrTestHelper::generateUuid()),
            new PrincipalIdentifier(StrTestHelper::generateUuid()),
            new Email('john.doe@example.com'),
            new ReplyContent($contentText),
            null,
            new DateTimeImmutable('2026-01-01 12:34:58'),
            new DateTimeImmutable('2026-01-01 12:34:57'),
        );

        $this->assertNull($reply->sentAt());
        $this->assertNotNull($reply->failedAt());
    }
}
