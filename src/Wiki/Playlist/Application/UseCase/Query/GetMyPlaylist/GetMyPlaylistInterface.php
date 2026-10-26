<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist;

use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface GetMyPlaylistInterface
{
    /**
     * @throws PlaylistNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(GetMyPlaylistInputPort $input, GetMyPlaylistOutputPort $output): void;
}
