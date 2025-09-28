<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveUpdatedSong;

use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface ApproveUpdatedSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function publishedSongIdentifier(): ?SongIdentifier;
}
