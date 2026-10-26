<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ListMyPlaylistsInput implements ListMyPlaylistsInputPort
{
    public function __construct(private PrincipalIdentifier $principalIdentifier)
    {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
