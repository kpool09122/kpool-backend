<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Factory\VideoLinkFactoryInterface;
use Source\Wiki\VideoLink\Domain\ValueObject\DisplayOrder;
use Source\Wiki\VideoLink\Domain\ValueObject\DurationSeconds;
use Source\Wiki\VideoLink\Domain\ValueObject\ThumbnailUrl;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoTitle;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLink\Infrastructure\Factory\VideoLinkFactory;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class VideoLinkFactoryTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app()->make(VideoLinkFactoryInterface::class);
        $this->assertInstanceOf(VideoLinkFactory::class, $factory);
    }

    /**
     * 正常系: VideoLink Entityが正しく作成されること.
     *
     * @throws BindingResolutionException
     */
    public function testCreateWithAllMetadata(): void
    {
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $publishedAt = new DateTimeImmutable('2026-10-01');
        $video = $this->app()->make(VideoLinkFactoryInterface::class)->create(ResourceType::TALENT, new WikiIdentifier(StrTestHelper::generateUuid()), new ExternalContentLink('https://example.com/video'), VideoUsage::MUSIC_VIDEO, new VideoTitle('Title'), new DisplayOrder(1), new ThumbnailUrl('https://example.com/thumb'), $publishedAt, new DurationSeconds(253), $principal);
        self::assertSame($principal, $video->creatorPrincipalIdentifier());
        self::assertSame(253, $video->durationSeconds()?->value());
        self::assertSame($publishedAt, $video->publishedAt());
        self::assertSame('https://example.com/thumb', (string) $video->thumbnailUrl());
    }

    public function testCreate(): void
    {
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new WikiIdentifier(StrTestHelper::generateUuid());
        $url = new ExternalContentLink('https://www.youtube.com/watch?v=test123');
        $videoUsage = VideoUsage::MUSIC_VIDEO;
        $title = 'Test Music Video';
        $displayOrder = 1;

        $factory = $this->app()->make(VideoLinkFactoryInterface::class);
        $videoLink = $factory->create(
            $resourceType,
            $resourceIdentifier,
            $url,
            $videoUsage,
            new VideoTitle($title),
            new DisplayOrder($displayOrder),
        );

        $this->assertTrue(UuidValidator::isValid((string) $videoLink->videoLinkIdentifier()));
        $this->assertSame($resourceType, $videoLink->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $videoLink->wikiIdentifier());
        $this->assertSame((string) $url, (string) $videoLink->url());
        $this->assertSame($videoUsage, $videoLink->videoUsage());
        $this->assertSame($title, (string) $videoLink->title());
        $this->assertSame($displayOrder, $videoLink->displayOrder()->value());
        self::assertNull($videoLink->thumbnailUrl());
        self::assertNull($videoLink->publishedAt());
        self::assertNull($videoLink->durationSeconds());
        self::assertNull($videoLink->creatorPrincipalIdentifier());
    }
}
