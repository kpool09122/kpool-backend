<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist\CreatePlaylistOutput;
use Tests\Helper\PlaylistTestHelper;

class CreatePlaylistOutputTest extends TestCase
{
    public function testStartsEmptyAndSerializesCreatedPlaylistContract(): void
    {
        $output = new CreatePlaylistOutput();
        self::assertSame([], $output->toArray());
        $playlist = PlaylistTestHelper::playlist();
        $output->setPlaylist($playlist);
        self::assertSame([
            'playlistIdentifier' => (string) $playlist->playlistIdentifier(),
            'name' => 'My playlist',
            'videoCount' => 0,
            'createdAt' => '2026-10-26T00:00:00+00:00',
            'updatedAt' => '2026-10-26T00:00:00+00:00',
        ], $output->toArray());
    }
}
