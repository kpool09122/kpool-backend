<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist\GetMyPlaylistOutput;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistDetailReadModel;
use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistItemReadModel;

class GetMyPlaylistOutputTest extends TestCase
{
    public function testSerializationAndReplacement(): void
    {
        $output = new GetMyPlaylistOutput();
        self::assertSame([], $output->toArray());
        $item = new PlaylistItemReadModel('item', null, 'youtube', 'abcdefghijk', 0, 'https://www.youtube.com/watch?v=abcdefghijk', 'Snapshot', null, null);
        $output->output(new PlaylistDetailReadModel('playlist', 'Name', 'created', 'updated', [$item]));
        self::assertSame([
            'playlistIdentifier' => 'playlist',
            'name' => 'Name',
            'videoCount' => 1,
            'createdAt' => 'created',
            'updatedAt' => 'updated',
            'items' => [[
                'playlistItemIdentifier' => 'item',
                'sourceVideoLinkIdentifier' => null,
                'provider' => 'youtube',
                'externalVideoId' => 'abcdefghijk',
                'position' => 0,
                'url' => 'https://www.youtube.com/watch?v=abcdefghijk',
                'title' => 'Snapshot',
                'thumbnailUrl' => null,
                'durationSeconds' => null,
            ]],
        ], $output->toArray());
        $output->output(new PlaylistDetailReadModel('empty', 'Empty', 'created', 'updated', []));
        self::assertSame(['playlistIdentifier' => 'empty', 'name' => 'Empty', 'videoCount' => 0, 'createdAt' => 'created', 'updatedAt' => 'updated', 'items' => []], $output->toArray());
    }
}
