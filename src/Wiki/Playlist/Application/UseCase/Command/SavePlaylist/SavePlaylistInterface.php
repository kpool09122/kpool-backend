<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use InvalidArgumentException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistItemLimitExceededException;
use Source\Wiki\Playlist\Domain\Exception\PlaylistNotFoundException;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;

interface SavePlaylistInterface
{
    /**
     * @throws InvalidArgumentException
     * @throws PlaylistItemLimitExceededException
     * @throws PlaylistNotFoundException
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     * @throws VideoLinkNotFoundException
     */
    public function process(SavePlaylistInputPort $input): void;
}
