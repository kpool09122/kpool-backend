<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface ListMyPlaylistsInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(ListMyPlaylistsInputPort $input, ListMyPlaylistsOutputPort $output): void;
}
