<?php

declare(strict_types=1);

namespace Tests\Wiki\VideoLink\Application\UseCase\Query;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Source\Wiki\VideoLink\Application\UseCase\Query\LikedVideoLinkReadModel;

class LikedVideoLinkReadModelTest extends TestCase
{
    /** @return array<string, array{?string, ?int}> */
    public static function metadata(): array
    {
        return ['unknown' => [null, null], 'known' => ['https://img.youtube.com/vi/abcdefghijk/default.jpg', 120], 'zero duration' => [null, 0]];
    }

    #[DataProvider('metadata')]
    public function testSerializesVideoAndSourceWikiMetadata(?string $thumbnail, ?int $duration): void
    {
        $video = new LikedVideoLinkReadModel('id', new YouTubeVideoUrl('https://www.youtube.com/watch?v=abcdefghijk'), 'Title', $thumbnail, $duration, 'en', 'song', 'sg-title', '2026-01-01T00:00:00+00:00');
        self::assertSame([
            'provider' => 'youtube', 'externalVideoId' => 'abcdefghijk', 'videoLinkIdentifier' => 'id', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk', 'title' => 'Title', 'thumbnailUrl' => $thumbnail, 'durationSeconds' => $duration,
            'language' => 'en', 'resourceType' => 'song', 'slug' => 'sg-title', 'likedAt' => '2026-01-01T00:00:00+00:00', 'playlists' => [],
        ], $video->toArray());
    }

    public function testIdentifiesTikTokVideo(): void
    {
        $url = 'https://www.tiktok.com/@scout2015/video/6718335390845095173';
        $model = new LikedVideoLinkReadModel('id', new TikTokVideoUrl($url), 'Title', null, null, 'en', 'song', 'sg-title', '2026-01-01T00:00:00+00:00');
        self::assertSame('tiktok', $model->provider);
        self::assertSame('6718335390845095173', $model->externalVideoId);
    }

    public function testRejectsUnresolvedTikTokUrl(): void
    {
        $url = 'https://vm.tiktok.com/abc/';
        $this->expectException(InvalidArgumentException::class);
        new LikedVideoLinkReadModel('id', new TikTokVideoUrl($url), 'Title', null, null, 'en', 'song', 'sg-title', '2026-01-01T00:00:00+00:00');
    }
}
