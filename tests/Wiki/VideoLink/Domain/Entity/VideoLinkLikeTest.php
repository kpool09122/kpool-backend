<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\VideoLink\Domain\Entity\VideoLinkLike;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkLikeIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkLikeTest extends TestCase
{
    public function testPreservesIndependentIdentifierVideoPrincipalAndCreationTime(): void
    {
        $id = new VideoLinkLikeIdentifier(StrTestHelper::generateUuid());
        $video = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $createdAt = new DateTimeImmutable('2026-10-01T00:00:00Z');
        $like = new VideoLinkLike($id, $video, $principal, $createdAt);
        self::assertSame($id, $like->videoLinkLikeIdentifier());
        self::assertSame($video, $like->videoLinkIdentifier());
        self::assertSame($principal, $like->principalIdentifier());
        self::assertSame($createdAt, $like->createdAt());
    }
}
