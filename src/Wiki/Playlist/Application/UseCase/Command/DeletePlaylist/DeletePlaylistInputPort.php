<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DeletePlaylistInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function playlistIdentifier(): PlaylistIdentifier;
}
