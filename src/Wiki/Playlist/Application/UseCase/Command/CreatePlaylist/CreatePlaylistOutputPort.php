<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use Source\Wiki\Playlist\Domain\Entity\Playlist;

interface CreatePlaylistOutputPort
{
    public function setPlaylist(Playlist $playlist): void;
}
