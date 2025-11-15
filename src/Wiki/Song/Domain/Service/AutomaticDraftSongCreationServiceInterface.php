<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Service;

use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Song\Domain\Entity\DraftSong;
use Source\Wiki\Song\Domain\ValueObject\AutomaticDraftSongCreationPayload;

interface AutomaticDraftSongCreationServiceInterface
{
    /**
     * @param AutomaticDraftSongCreationPayload $payload
     * @param Principal $requestedBy
     * @return DraftSong
     */
    public function create(
        AutomaticDraftSongCreationPayload $payload,
        Principal $requestedBy,
    ): DraftSong;
}
