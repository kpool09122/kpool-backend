<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;

class PlaylistItemTest extends TestCase
{
    public function testPreservesTikTokSnapshotAndImageWhenReordering(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $path = new ImagePath('images/cover.webp');
        $item = new PlaylistItem($id, null, VideoProvider::TIKTOK, new ExternalVideoId('6718335390845095173'), new TikTokVideoUrl('https://www.tiktok.com/@scout2015/video/6718335390845095173'), new VideoTitle('Title'), null, null, new PlaylistItemPosition(0), $path);
        $reordered = $item->atPosition(new PlaylistItemPosition(1));
        self::assertSame($path, $reordered->thumbnailPath());
        self::assertSame('6718335390845095173', (string) $reordered->externalVideoId());
        self::assertSame(VideoProvider::TIKTOK, $reordered->provider());
        self::assertSame(1, $reordered->position()->value());
    }

    public function testPreservesSourceAndSnapshotValueObjects(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $source = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $item = PlaylistTestHelper::item($id, 0, $source);
        self::assertSame($id, $item->playlistIdentifier());
        self::assertSame($source, $item->videoLinkIdentifier());
        self::assertSame(VideoProvider::YOUTUBE, $item->provider());
        self::assertSame('00000000000', (string) $item->externalVideoId());
        self::assertSame('https://www.youtube.com/watch?v=00000000000', (string) $item->url());
        self::assertSame('Snapshot title', (string) $item->title());
        self::assertSame(0, $item->position()->value());
    }

    public function testAcceptsNullableSourceAndMetadata(): void
    {
        $item = new PlaylistItem(new PlaylistIdentifier(StrTestHelper::generateUuid()), null, VideoProvider::YOUTUBE, new ExternalVideoId('00000000000'), new YouTubeVideoUrl('https://youtu.be/00000000000'), new VideoTitle('Title'), null, null, new PlaylistItemPosition(0));
        self::assertNull($item->videoLinkIdentifier());
        self::assertNull($item->thumbnailUrl());
        self::assertNull($item->durationSeconds());
    }

    /** @return list<array{string}> */
    public static function shortUrls(): array
    {
        return [['https://vm.tiktok.com/abc/'], ['https://vt.tiktok.com/abc/']];
    }

    #[DataProvider('shortUrls')]
    public function testRejectsUnresolvedTikTokSnapshot(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlaylistItem(new PlaylistIdentifier(StrTestHelper::generateUuid()), null, VideoProvider::TIKTOK, new ExternalVideoId('6718335390845095173'), new TikTokVideoUrl($url), new VideoTitle('Title'), null, null, new PlaylistItemPosition(0));
    }

    public function testRejectsProviderThatDoesNotMatchUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlaylistItem(new PlaylistIdentifier(StrTestHelper::generateUuid()), null, VideoProvider::YOUTUBE, new ExternalVideoId('6718335390845095173'), new TikTokVideoUrl('https://www.tiktok.com/@scout2015/video/6718335390845095173'), new VideoTitle('Title'), null, null, new PlaylistItemPosition(0));
    }

    public function testRejectsMismatchedVideoIdentityAndSnapshot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlaylistItem(new PlaylistIdentifier(StrTestHelper::generateUuid()), null, VideoProvider::YOUTUBE, new ExternalVideoId('11111111111'), new YouTubeVideoUrl('https://youtu.be/00000000000'), new VideoTitle('Title'), null, null, new PlaylistItemPosition(0));
    }
}
