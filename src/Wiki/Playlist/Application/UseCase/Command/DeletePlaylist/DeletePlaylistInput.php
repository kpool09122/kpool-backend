<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\DeletePlaylist;

use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class DeletePlaylistInput implements DeletePlaylistInputPort
{
    public function __construct(private PrincipalIdentifier $principalIdentifier, private PlaylistIdentifier $playlistIdentifier)
    {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function playlistIdentifier(): PlaylistIdentifier
    {
        return $this->playlistIdentifier;
    }
}
