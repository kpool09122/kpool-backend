<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveSong;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class ApproveSongInput implements ApproveSongInputPort
{
    public function __construct(
        private SongIdentifier  $songIdentifier,
        private ?SongIdentifier $publishedSongIdentifier,
        private Principal       $principal,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function publishedSongIdentifier(): ?SongIdentifier
    {
        return $this->publishedSongIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
