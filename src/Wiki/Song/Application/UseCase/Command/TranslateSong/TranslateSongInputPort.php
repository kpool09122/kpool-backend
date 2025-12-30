<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\TranslateSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface TranslateSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
