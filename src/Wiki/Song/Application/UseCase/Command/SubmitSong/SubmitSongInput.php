<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class SubmitSongInput implements SubmitSongInputPort
{
    public function __construct(
        private SongIdentifier $songIdentifier,
        private Principal $principal,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
