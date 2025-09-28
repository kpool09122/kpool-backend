<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectUpdatedSong;

use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class RejectUpdatedSongInput implements RejectUpdatedSongInputPort
{
    public function __construct(
        private SongIdentifier $songIdentifier,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }
}
