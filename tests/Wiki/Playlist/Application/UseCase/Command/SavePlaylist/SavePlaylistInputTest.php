<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistInput;
use Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist\SavePlaylistItemInput;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Playlist\Domain\ValueObject\VideoProvider;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;

class SavePlaylistInputTest extends TestCase
{
    public function testPreservesExactValueObjectsAndSelectionOrder(): void
    {
        $playlist = PlaylistTestHelper::playlist();
        $name = new PlaylistName('Edited');
        $itemId = new ExternalVideoId('abcdefghijk');
        $videoId = new VideoLinkIdentifier(StrTestHelper::generateUuid());
        $existing = new SavePlaylistItemInput(provider: VideoProvider::YOUTUBE, externalVideoId: $itemId);
        $added = new SavePlaylistItemInput(videoLinkIdentifier: $videoId);
        $input = new SavePlaylistInput($playlist->principalIdentifier(), $playlist->playlistIdentifier(), $name, [$existing, $added]);
        self::assertSame($playlist->principalIdentifier(), $input->principalIdentifier());
        self::assertSame($playlist->playlistIdentifier(), $input->playlistIdentifier());
        self::assertSame($name, $input->name());
        self::assertSame([$existing, $added], $input->items());
        self::assertSame($itemId, $existing->externalVideoId);
        self::assertNull($existing->videoLinkIdentifier);
        self::assertSame($videoId, $added->videoLinkIdentifier);
        self::assertNull($added->provider);
        self::assertNull($added->externalVideoId);
        self::assertSame([], (new SavePlaylistInput($playlist->principalIdentifier(), $playlist->playlistIdentifier(), $name, []))->items());
    }

    public function testRejectsMissingSelection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SavePlaylistItemInput();
    }

    public function testRejectsAmbiguousSelection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SavePlaylistItemInput(VideoProvider::YOUTUBE, new ExternalVideoId('abcdefghijk'), new VideoLinkIdentifier(StrTestHelper::generateUuid()));
    }
}
