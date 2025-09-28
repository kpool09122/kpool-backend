<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectUpdatedSong;

use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface RejectUpdatedSongInputPort
{
    public function songIdentifier(): SongIdentifier;
}
