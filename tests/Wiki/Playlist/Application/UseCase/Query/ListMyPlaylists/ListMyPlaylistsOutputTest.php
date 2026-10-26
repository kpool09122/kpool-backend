<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists\ListMyPlaylistsOutput;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistListItemReadModel;

class ListMyPlaylistsOutputTest extends TestCase
{
    public function testSerializesAndReplacesPlaylistsIncludingEmptyList(): void
    {
        $output = new ListMyPlaylistsOutput();
        self::assertSame(['playlists' => []], $output->toArray());
        $output->output([new PlaylistListItemReadModel('playlist-id', '名前', 50, '2026-10-26T00:00:00+00:00', '2026-10-27T01:02:03+00:00')]);
        self::assertSame(['playlists' => [[
            'playlistIdentifier' => 'playlist-id',
            'name' => '名前',
            'videoCount' => 50,
            'createdAt' => '2026-10-26T00:00:00+00:00',
            'updatedAt' => '2026-10-27T01:02:03+00:00',
        ]]], $output->toArray());
        $output->output([]);
        self::assertSame(['playlists' => []], $output->toArray());
    }
}
