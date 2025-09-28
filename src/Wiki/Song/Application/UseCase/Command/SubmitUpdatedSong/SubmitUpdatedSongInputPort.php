<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitUpdatedSong;

use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SubmitUpdatedSongInputPort
{
    public function songIdentifier(): SongIdentifier;
}
