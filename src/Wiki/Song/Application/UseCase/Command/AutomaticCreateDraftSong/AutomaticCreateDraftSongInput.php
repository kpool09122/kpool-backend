<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;

readonly class AutomaticCreateDraftSongInput implements AutomaticCreateDraftSongInputPort
{
    public function __construct(
        private AutomaticDraftSongCreationPayload $payload,
        private PrincipalIdentifier               $principalIdentifier,
    ) {
    }

    public function payload(): AutomaticDraftSongCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
