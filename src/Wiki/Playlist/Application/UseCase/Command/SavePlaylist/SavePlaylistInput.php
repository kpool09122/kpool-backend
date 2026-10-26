<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\SavePlaylist;

use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class SavePlaylistInput implements SavePlaylistInputPort
{
    /** @param list<SavePlaylistItemInput> $items */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private PlaylistIdentifier $playlistIdentifier,
        private PlaylistName $name,
        private array $items,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function playlistIdentifier(): PlaylistIdentifier
    {
        return $this->playlistIdentifier;
    }

    public function name(): PlaylistName
    {
        return $this->name;
    }

    public function items(): array
    {
        return $this->items;
    }
}
