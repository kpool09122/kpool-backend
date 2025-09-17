<?php

namespace Businesses\Wiki\Song\UseCase\Query\GetSong;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface GetSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function translation(): Translation;
}
