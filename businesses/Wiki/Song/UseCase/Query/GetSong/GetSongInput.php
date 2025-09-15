<?php

namespace Businesses\Wiki\Song\UseCase\Query\GetSong;

use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class GetSongInput implements GetSongInputPort
{
    public function __construct(
        private SongIdentifier $songIdentifier
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }
}
