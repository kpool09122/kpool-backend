<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\PlaylistTestHelper;
use Tests\Helper\StrTestHelper;

class PlaylistTest extends TestCase
{
    public function testCreatesEmptyOwnedPlaylist(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $owner = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $name = new PlaylistName('曲');
        $at = new DateTimeImmutable('2026-10-26T00:00:00+00:00');
        $playlist = new Playlist($id, $owner, $name, $at, $at);
        self::assertSame($id, $playlist->playlistIdentifier());
        self::assertSame($owner, $playlist->principalIdentifier());
        self::assertSame($name, $playlist->name());
        self::assertSame($at, $playlist->createdAt());
        self::assertSame($at, $playlist->updatedAt());
        self::assertSame([], $playlist->items());
        self::assertSame(0, $playlist->videoCount());
    }

    public function testAllowsFiftyUniqueItems(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $items[] = PlaylistTestHelper::item($id, $i);
        }
        $playlist = PlaylistTestHelper::playlist($id, items: $items);
        self::assertSame($items, $playlist->items());
        self::assertSame(50, $playlist->videoCount());
    }

    public function testRejectsFiftyOneItems(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $items = [];
        for ($i = 0; $i < 51; ++$i) {
            $items[] = PlaylistTestHelper::item($id, $i);
        }
        $this->expectException(PlaylistItemLimitExceededException::class);
        PlaylistTestHelper::playlist($id, items: $items);
    }

    public function testRejectsSameVideoEvenWithDifferentSourceAndIdentifier(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $this->expectException(InvalidArgumentException::class);
        PlaylistTestHelper::playlist($id, items: [PlaylistTestHelper::item($id, 0), PlaylistTestHelper::item($id, 1, externalId: '00000000000')]);
    }

    public function testRejectsDuplicatePositions(): void
    {
        $id = new PlaylistIdentifier(StrTestHelper::generateUuid());
        $this->expectException(InvalidArgumentException::class);
        PlaylistTestHelper::playlist($id, items: [PlaylistTestHelper::item($id, 0), PlaylistTestHelper::item($id, 0, externalId: '11111111111')]);
    }

    public function testRejectsItemFromAnotherPlaylist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlaylistTestHelper::playlist(items: [PlaylistTestHelper::item(new PlaylistIdentifier(StrTestHelper::generateUuid()), 0)]);
    }
}
