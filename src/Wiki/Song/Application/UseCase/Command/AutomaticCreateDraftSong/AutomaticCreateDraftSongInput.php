<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;

readonly class AutomaticCreateDraftSongInput implements AutomaticCreateDraftSongInputPort
{
    public function __construct(
        private AutomaticDraftSongCreationPayload $payload,
        private Principal $principal,
    ) {
    }

    public function payload(): AutomaticDraftSongCreationPayload
    {
        return $this->payload;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
