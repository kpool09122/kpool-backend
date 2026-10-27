<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoWatchRequest;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoWatchRequestIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class VideoWatchRequestTest extends TestCase
{
    public function testPreservesAllIdentityAndTimestampObjects(): void
    {
        $id = new VideoWatchRequestIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $sentAt = new DateTimeImmutable('2026-10-28 00:00:00 UTC');
        $request = new VideoWatchRequest($id, $video, $wiki, $principal, $sentAt);
        self::assertSame($id, $request->videoWatchRequestIdentifier());
        self::assertSame($video, $request->videoLinkIdentifier());
        self::assertSame($wiki, $request->wikiIdentifier());
        self::assertSame($principal, $request->principalIdentifier());
        self::assertSame($sentAt, $request->sentAt());
    }
}
