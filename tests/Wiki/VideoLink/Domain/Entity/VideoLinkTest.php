<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\DurationSeconds;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class VideoLinkTest extends TestCase
{
    public function testConstructorPreservesAllValues(): void
    {
        $identifier = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $wikiIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $url = new ExternalContentLink('https://www.youtube.com/watch?v=test');
        $publishedAt = new DateTimeImmutable('2026-10-01');
        $createdAt = new DateTimeImmutable('2026-10-23');
        $video = new VideoLink($identifier, ResourceType::TALENT, $wikiIdentifier, $url, VideoUsage::MUSIC_VIDEO, new VideoTitle('Title'), new ThumbnailUrl('https://example.com/thumb.jpg'), $publishedAt, $createdAt, new DurationSeconds(253), $principalIdentifier);
        self::assertSame($identifier, $video->videoLinkIdentifier());
        self::assertSame($wikiIdentifier, $video->wikiIdentifier());
        self::assertSame(ResourceType::TALENT, $video->resourceType());
        self::assertSame($url, $video->url());
        self::assertSame(VideoUsage::MUSIC_VIDEO, $video->videoUsage());
        self::assertSame('Title', (string) $video->title());
        self::assertSame('https://example.com/thumb.jpg', (string) $video->thumbnailUrl());
        self::assertSame($publishedAt, $video->publishedAt());
        self::assertSame($createdAt, $video->createdAt());
        self::assertSame(253, $video->durationSeconds()?->value());
        self::assertSame($principalIdentifier, $video->creatorPrincipalIdentifier());
    }
}
