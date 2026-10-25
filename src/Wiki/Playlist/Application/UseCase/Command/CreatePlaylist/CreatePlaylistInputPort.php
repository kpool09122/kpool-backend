<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface CreatePlaylistInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function name(): PlaylistName;
}
