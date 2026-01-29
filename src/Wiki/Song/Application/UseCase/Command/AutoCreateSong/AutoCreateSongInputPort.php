<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutoCreateSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutoSongCreationPayload;

interface AutoCreateSongInputPort
{
    public function payload(): AutoSongCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
