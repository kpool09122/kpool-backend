<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\Entity\PlaylistItem;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;

class PlaylistItemTest extends TestCase
{
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
        self::assertSame(0, $item->position());
    }

    public function testAcceptsNullableSourceAndMetadata(): void
    {
        $item = new PlaylistItem(new PlaylistItemIdentifier(StrTestHelper::generateUuid()), new PlaylistIdentifier(StrTestHelper::generateUuid()), null, VideoProvider::YOUTUBE, new ExternalVideoId('00000000000'), new YouTubeVideoUrl('https://youtu.be/00000000000'), new VideoTitle('Title'), null, null, 0);
        self::assertNull($item->videoLinkIdentifier());
        self::assertNull($item->thumbnailUrl());
        self::assertNull($item->durationSeconds());
    }

    public function testRejectsNegativePosition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlaylistTestHelper::item(new PlaylistIdentifier(StrTestHelper::generateUuid()), -1, externalId: '00000000000');
    }

    public function testRejectsMismatchedVideoIdentityAndSnapshot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlaylistItem(new PlaylistItemIdentifier(StrTestHelper::generateUuid()), new PlaylistIdentifier(StrTestHelper::generateUuid()), null, VideoProvider::YOUTUBE, new ExternalVideoId('11111111111'), new YouTubeVideoUrl('https://youtu.be/00000000000'), new VideoTitle('Title'), null, null, 0);
    }
}
