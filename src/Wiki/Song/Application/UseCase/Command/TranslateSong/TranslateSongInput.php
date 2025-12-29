<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\TranslateSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class TranslateSongInput implements TranslateSongInputPort
{
    public function __construct(
        private SongIdentifier      $songIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function songIdentifier(): SongIdentifier
    {
        return $this->songIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
