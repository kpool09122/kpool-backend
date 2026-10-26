<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistListItemReadModel;

class PlaylistListItemReadModelTest extends TestCase
{
    public function testSerializesAllFields(): void
    {
        $playlist = new PlaylistListItemReadModel('playlist-id', '名前', 3, '2026-10-26T00:00:00+00:00', '2026-10-27T00:00:00+00:00');
        self::assertSame([
            'playlistIdentifier' => 'playlist-id',
            'name' => '名前',
            'videoCount' => 3,
            'createdAt' => '2026-10-26T00:00:00+00:00',
            'updatedAt' => '2026-10-27T00:00:00+00:00',
        ], $playlist->toArray());
    }
}
