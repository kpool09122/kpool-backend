<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Application\UseCase\Command\CreateVideoLink\CreateVideoLinkInput;
use Source\Wiki\VideoLink\Domain\ValueObject\DisplayOrder;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;

class CreateVideoLinkInputTest extends TestCase
{
    public function testAccessorsPreserveValues(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $wiki = new WikiIdentifier(StrTestHelper::generateUuid());
        $url = new ExternalContentLink('https://example.com/video');
        $publishedAt = new DateTimeImmutable('2026-10-01');
        $input = new CreateVideoLinkInput($principal, ResourceType::TALENT, $wiki, $url, VideoUsage::MUSIC_VIDEO, new VideoTitle('Title'), new DisplayOrder(0), new DurationSeconds(0), new ThumbnailUrl('https://example.com/thumb'), $publishedAt);
        self::assertSame($principal, $input->principalIdentifier());
        self::assertSame(ResourceType::TALENT, $input->resourceType());
        self::assertSame($wiki, $input->wikiIdentifier());
        self::assertSame($url, $input->url());
        self::assertSame(VideoUsage::MUSIC_VIDEO, $input->videoUsage());
        self::assertSame('Title', (string) $input->title());
        self::assertSame(0, $input->displayOrder()->value());
        self::assertSame(0, $input->durationSeconds()->value());
        self::assertSame('https://example.com/thumb', (string) $input->thumbnailUrl());
        self::assertSame($publishedAt, $input->publishedAt());
    }

    public function testAutomaticUsageIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CreateVideoLinkInput(new PrincipalIdentifier(StrTestHelper::generateUuid()), ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new ExternalContentLink('https://example.com/video'), VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, new VideoTitle('Title'), new DisplayOrder(0), new DurationSeconds(1));
    }
}
