<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistListItemReadModel;

interface ListMyPlaylistsOutputPort
{
    /** @param list<PlaylistListItemReadModel> $playlists */
    public function output(array $playlists): void;
}
