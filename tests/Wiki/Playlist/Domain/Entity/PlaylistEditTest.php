<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Tests\Helper\PlaylistTestHelper;

class PlaylistEditTest extends TestCase
{
    public function testReplacesNameMembershipAndOrderWithoutChangingSnapshots(): void
    {
        $playlist = PlaylistTestHelper::playlist();
        $first = PlaylistTestHelper::item($playlist->playlistIdentifier(), 0);
        $second = PlaylistTestHelper::item($playlist->playlistIdentifier(), 1);
        $at = new DateTimeImmutable('2026-10-27');
        $identifier = $playlist->playlistIdentifier();
        $owner = $playlist->principalIdentifier();
        $createdAt = $playlist->createdAt();
        $samePlaylist = $playlist;
        $playlist->edit(new PlaylistName(' New '), [$second, $first], $at);
        self::assertSame('New', (string) $playlist->name());
        self::assertSame($identifier, $playlist->playlistIdentifier());
        self::assertSame($owner, $playlist->principalIdentifier());
        self::assertSame($createdAt, $playlist->createdAt());
        self::assertSame($at, $playlist->updatedAt());
        foreach ([$second, $first] as $position => $expected) {
            $actual = $playlist->items()[$position];
            self::assertSame($position, $actual->position()->value());
            self::assertSame($expected->videoLinkIdentifier(), $actual->videoLinkIdentifier());
            self::assertSame($expected->provider(), $actual->provider());
            self::assertSame($expected->externalVideoId(), $actual->externalVideoId());
            self::assertSame($expected->url(), $actual->url());
            self::assertSame($expected->title(), $actual->title());
            self::assertSame($expected->thumbnailUrl(), $actual->thumbnailUrl());
            self::assertSame($expected->durationSeconds(), $actual->durationSeconds());
        }
        self::assertSame('New', (string) $samePlaylist->name());
        self::assertSame(2, $samePlaylist->videoCount());
        self::assertSame(0, $first->position()->value());
        self::assertSame(1, $second->position()->value());
        $playlist->edit(new PlaylistName('Empty'), [], $at);
        self::assertSame([], $playlist->items());
    }

    public function testRejectsDuplicateVideoWithoutChangingCurrentState(): void
    {
        $playlist = PlaylistTestHelper::playlist();
        $originalItems = [PlaylistTestHelper::item($playlist->playlistIdentifier(), 0)];
        $originalName = new PlaylistName('Original');
        $originalUpdatedAt = new DateTimeImmutable('2026-10-26');
        $playlist->edit($originalName, $originalItems, $originalUpdatedAt);
        $savedItems = $playlist->items();

        try {
            $playlist->edit(new PlaylistName('Duplicate'), [PlaylistTestHelper::item($playlist->playlistIdentifier(), 0), PlaylistTestHelper::item($playlist->playlistIdentifier(), 1, externalId: '00000000000')], new DateTimeImmutable('2026-10-27'));
            self::fail('Duplicate videos were accepted.');
        } catch (InvalidArgumentException) {
            self::assertSame($originalName, $playlist->name());
            self::assertSame($savedItems, $playlist->items());
            self::assertSame($originalUpdatedAt, $playlist->updatedAt());
        }
    }

    public function testAcceptsFiftyAndRejectsFiftyOne(): void
    {
        $playlist = PlaylistTestHelper::playlist();
        $items = [];
        for ($i = 0; $i < 50; ++$i) {
            $items[] = PlaylistTestHelper::item($playlist->playlistIdentifier(), $i);
        }
        $playlist->edit(new PlaylistName('Fifty'), $items, new DateTimeImmutable());
        self::assertSame(50, $playlist->videoCount());
        $items[] = PlaylistTestHelper::item($playlist->playlistIdentifier(), 50);
        $savedItems = $playlist->items();
        $updatedAt = $playlist->updatedAt();

        try {
            $playlist->edit(new PlaylistName('Too many'), $items, new DateTimeImmutable());
            self::fail('Fifty one items were accepted.');
        } catch (PlaylistItemLimitExceededException) {
            self::assertSame('Fifty', (string) $playlist->name());
            self::assertSame($savedItems, $playlist->items());
            self::assertSame($updatedAt, $playlist->updatedAt());
        }
    }
}
