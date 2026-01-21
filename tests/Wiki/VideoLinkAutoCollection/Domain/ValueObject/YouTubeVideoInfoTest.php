<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLinkAutoCollection\Domain\ValueObject;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject\YouTubeVideoInfo;

class YouTubeVideoInfoTest extends TestCase
{
    /**
     * 正常系: インスタンスを作成できること.
     */
    public function test__construct(): void
    {
        $publishedAt = new DateTimeImmutable('2024-01-15T10:30:00Z');

        $videoInfo = new YouTubeVideoInfo(
            videoId: 'dQw4w9WgXcQ',
            title: 'テスト動画タイトル',
            url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            thumbnailUrl: 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            videoUsage: VideoUsage::YOUTUBE_AUTO_VIEW_COUNT,
            publishedAt: $publishedAt,
        );

        $this->assertSame('dQw4w9WgXcQ', $videoInfo->videoId());
        $this->assertSame('テスト動画タイトル', $videoInfo->title());
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $videoInfo->url());
        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $videoInfo->thumbnailUrl());
        $this->assertSame(VideoUsage::YOUTUBE_AUTO_VIEW_COUNT, $videoInfo->videoUsage());
        $this->assertSame($publishedAt, $videoInfo->publishedAt());
    }

    /**
     * 正常系: 高評価数カテゴリの動画情報を作成できること.
     */
    public function testCreateWithLikeCountUsage(): void
    {
        $videoInfo = new YouTubeVideoInfo(
            videoId: 'abc123',
            title: '高評価動画',
            url: 'https://www.youtube.com/watch?v=abc123',
            thumbnailUrl: 'https://i.ytimg.com/vi/abc123/hqdefault.jpg',
            videoUsage: VideoUsage::YOUTUBE_AUTO_LIKE_COUNT,
            publishedAt: new DateTimeImmutable(),
        );

        $this->assertSame(VideoUsage::YOUTUBE_AUTO_LIKE_COUNT, $videoInfo->videoUsage());
        $this->assertTrue($videoInfo->videoUsage()->isAutoCollected());
    }

    /**
     * 正常系: 直近人気カテゴリの動画情報を作成できること.
     */
    public function testCreateWithRecentPopularUsage(): void
    {
        $videoInfo = new YouTubeVideoInfo(
            videoId: 'xyz789',
            title: '直近人気動画',
            url: 'https://www.youtube.com/watch?v=xyz789',
            thumbnailUrl: 'https://i.ytimg.com/vi/xyz789/hqdefault.jpg',
            videoUsage: VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR,
            publishedAt: new DateTimeImmutable(),
        );

        $this->assertSame(VideoUsage::YOUTUBE_AUTO_RECENT_POPULAR, $videoInfo->videoUsage());
        $this->assertTrue($videoInfo->videoUsage()->isAutoCollected());
    }
}
