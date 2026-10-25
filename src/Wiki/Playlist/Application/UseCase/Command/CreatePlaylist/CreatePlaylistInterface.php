<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use Source\Wiki\Playlist\Domain\Exception\PlaylistLimitExceededException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface CreatePlaylistInterface
{
    /**
     * @throws PlaylistLimitExceededException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreatePlaylistInputPort $input, CreatePlaylistOutputPort $output): void;
}
