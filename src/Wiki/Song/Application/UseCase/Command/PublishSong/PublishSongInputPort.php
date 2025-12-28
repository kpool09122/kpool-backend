<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\PublishSong;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface PublishSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function publishedSongIdentifier(): ?SongIdentifier;

    public function principal(): Principal;
}
