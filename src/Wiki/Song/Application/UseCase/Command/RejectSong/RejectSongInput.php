<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\RejectSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

readonly class RejectSongInput implements RejectSongInputPort
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
