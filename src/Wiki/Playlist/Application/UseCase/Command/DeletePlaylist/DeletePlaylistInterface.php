<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface DeletePlaylistInterface
{
    /**
     * @throws PlaylistNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(DeletePlaylistInputPort $input): void;
}
