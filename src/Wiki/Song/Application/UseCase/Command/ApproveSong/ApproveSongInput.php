<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class ApproveSongInput implements ApproveSongInputPort
{
    public function __construct(
        private SongIdentifier      $songIdentifier,
        private ?SongIdentifier     $publishedSongIdentifier,
        private PrincipalIdentifier $principalIdentifier,
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

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
