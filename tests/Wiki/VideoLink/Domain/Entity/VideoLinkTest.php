<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Tests\Helper\StrTestHelper;

class VideoLinkTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     */
    public function test__construct(): void
    {
        $videoLinkIdentifier = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $resourceType = ResourceType::TALENT;
        $resourceIdentifier = new ResourceIdentifier(StrTestHelper::generateUuid());
        $url = new ExternalContentLink('https://www.youtube.com/watch?v=test123');
        $videoUsage = VideoUsage::MUSIC_VIDEO;
        $title = 'Test Music Video';
        $thumbnailUrl = 'https://i.ytimg.com/vi/test123/hqdefault.jpg';
        $publishedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');
        $displayOrder = 1;
        $createdAt = new DateTimeImmutable();

        $videoLink = new VideoLink(
            $videoLinkIdentifier,
            $resourceType,
            $resourceIdentifier,
            $url,
            $videoUsage,
            $title,
            $thumbnailUrl,
            $publishedAt,
            $displayOrder,
            $createdAt,
        );

        $this->assertSame((string) $videoLinkIdentifier, (string) $videoLink->videoLinkIdentifier());
        $this->assertSame($resourceType, $videoLink->resourceType());
        $this->assertSame((string) $resourceIdentifier, (string) $videoLink->resourceIdentifier());
        $this->assertSame((string) $url, (string) $videoLink->url());
        $this->assertSame($videoUsage, $videoLink->videoUsage());
        $this->assertSame($title, $videoLink->title());
        $this->assertSame($thumbnailUrl, $videoLink->thumbnailUrl());
        $this->assertSame($publishedAt, $videoLink->publishedAt());
        $this->assertSame($displayOrder, $videoLink->displayOrder());
        $this->assertSame($createdAt, $videoLink->createdAt());
    }

    /**
     * 正常系: urlのsetterが正しく動作すること.
     */
    public function testSetUrl(): void
    {
        $videoLink = $this->createDummyVideoLink();
        $newUrl = new ExternalContentLink('https://www.youtube.com/watch?v=newvideo');

        $videoLink->setUrl($newUrl);

        $this->assertSame((string) $newUrl, (string) $videoLink->url());
    }

    /**
     * 正常系: videoUsageのsetterが正しく動作すること.
     */
    public function testSetVideoUsage(): void
    {
        $videoLink = $this->createDummyVideoLink();

        $videoLink->setVideoUsage(VideoUsage::LIVE);

        $this->assertSame(VideoUsage::LIVE, $videoLink->videoUsage());
    }

    /**
     * 正常系: titleのsetterが正しく動作すること.
     */
    public function testSetTitle(): void
    {
        $videoLink = $this->createDummyVideoLink();
        $newTitle = 'Updated Title';

        $videoLink->setTitle($newTitle);

        $this->assertSame($newTitle, $videoLink->title());
    }

    /**
     * 正常系: displayOrderのsetterが正しく動作すること.
     */
    public function testSetDisplayOrder(): void
    {
        $videoLink = $this->createDummyVideoLink();

        $videoLink->setDisplayOrder(5);

        $this->assertSame(5, $videoLink->displayOrder());
    }

    private function createDummyVideoLink(): VideoLink
    {
        return new VideoLink(
            new VideoLinkIdentifier(StrTestHelper::generateUuid()),
            ResourceType::TALENT,
            new ResourceIdentifier(StrTestHelper::generateUuid()),
            new ExternalContentLink('https://www.youtube.com/watch?v=test123'),
            VideoUsage::MUSIC_VIDEO,
            'Test Music Video',
            null,
            null,
            1,
            new DateTimeImmutable(),
        );
    }
}
