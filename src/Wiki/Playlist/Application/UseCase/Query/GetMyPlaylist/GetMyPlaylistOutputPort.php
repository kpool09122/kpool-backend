<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist;

use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistDetailReadModel;

interface GetMyPlaylistOutputPort
{
    public function output(PlaylistDetailReadModel $playlist): void;
}
