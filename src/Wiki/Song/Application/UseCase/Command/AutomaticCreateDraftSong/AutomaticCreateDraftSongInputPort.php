<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;

interface AutomaticCreateDraftSongInputPort
{
    public function payload(): AutomaticDraftSongCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
