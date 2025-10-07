<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\TranslateSong;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class TranslateSongInput implements TranslateSongInputPort
{
    public function __construct(
        private SongIdentifier $songIdentifier,
        private Principal $principal,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
