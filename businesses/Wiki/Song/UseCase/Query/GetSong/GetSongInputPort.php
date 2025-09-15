<?php

namespace Businesses\Wiki\Song\UseCase\Query\GetSong;

use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface GetSongInputPort
{
    public function songIdentifier(): SongIdentifier;
}
