<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ListMyPlaylistsInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;
}
