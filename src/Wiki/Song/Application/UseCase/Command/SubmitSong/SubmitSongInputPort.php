<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\SubmitSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SubmitSongInputPort
{
    public function songIdentifier(): SongIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
