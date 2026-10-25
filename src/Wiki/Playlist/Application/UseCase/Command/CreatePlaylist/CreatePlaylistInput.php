<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class CreatePlaylistInput implements CreatePlaylistInputPort
{
    public function __construct(private PrincipalIdentifier $principalIdentifier, private PlaylistName $name)
    {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function name(): PlaylistName
    {
        return $this->name;
    }
}
