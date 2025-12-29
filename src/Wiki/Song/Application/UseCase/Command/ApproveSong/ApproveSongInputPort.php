<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\ApproveSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface ApproveSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function publishedSongIdentifier(): ?SongIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
