<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface SavePlaylistInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function playlistIdentifier(): PlaylistIdentifier;

    public function name(): PlaylistName;

    /** @return list<SavePlaylistItemInput> */
    public function items(): array;
}
