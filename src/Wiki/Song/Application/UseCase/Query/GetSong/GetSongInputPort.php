<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Query\GetSong;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface GetSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function translation(): Translation;
}
