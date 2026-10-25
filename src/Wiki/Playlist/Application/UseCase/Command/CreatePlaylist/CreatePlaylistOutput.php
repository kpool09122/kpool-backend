<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Command\CreatePlaylist;

use DateTimeInterface;
use Source\Wiki\Playlist\Domain\Entity\Playlist;

class CreatePlaylistOutput implements CreatePlaylistOutputPort
{
    private ?Playlist $playlist = null;

    public function setPlaylist(Playlist $playlist): void
    {
        $this->playlist = $playlist;
    }

    /** @return array{}|array{playlistIdentifier: string, name: string, videoCount: int, createdAt: string, updatedAt: string} */
    public function toArray(): array
    {
        if ($this->playlist === null) {
            return [];
        }

        return [
            'playlistIdentifier' => (string) $this->playlist->playlistIdentifier(),
            'name' => (string) $this->playlist->name(),
            'videoCount' => $this->playlist->videoCount(),
            'createdAt' => $this->playlist->createdAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $this->playlist->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
