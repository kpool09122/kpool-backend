<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveSong;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface ApproveSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function publishedSongIdentifier(): ?SongIdentifier;

    public function principal(): Principal;
}
