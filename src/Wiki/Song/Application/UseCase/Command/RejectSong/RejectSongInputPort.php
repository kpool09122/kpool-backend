<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectSong;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface RejectSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function principal(): Principal;
}
