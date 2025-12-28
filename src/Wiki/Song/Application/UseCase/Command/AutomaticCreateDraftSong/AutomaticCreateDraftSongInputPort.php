<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Application\UseCase\Command\AutomaticCreateDraftSong;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;

interface AutomaticCreateDraftSongInputPort
{
    public function payload(): AutomaticDraftSongCreationPayload;

    public function principal(): Principal;
}
