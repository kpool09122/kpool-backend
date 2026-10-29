<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkWatch;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkWatchIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkWatchTest extends TestCase
{
    public function testPreservesIdentifierVideoOwnerPrincipalAndCompletionTime(): void
    {
        $id = new VideoLinkWatchIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $owner = new AccountIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $watchedAt = new DateTimeImmutable('2026-10-29T00:00:00Z');
        $watch = new VideoLinkWatch($id, $video, $owner, $principal, $watchedAt);
        self::assertSame($id, $watch->videoLinkWatchIdentifier());
        self::assertSame($video, $watch->videoLinkIdentifier());
        self::assertSame($owner, $watch->ownerAccountIdentifier());
        self::assertSame($principal, $watch->principalIdentifier());
        self::assertSame($watchedAt, $watch->watchedAt());
    }
}
