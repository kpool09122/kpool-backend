<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Service;

use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\ValueObject\AutomaticDraftTalentCreationPayload;

interface AutomaticDraftTalentCreationServiceInterface
{
    /**
     * @param AutomaticDraftTalentCreationPayload $payload
     * @param Principal $requestedBy
     * @return DraftTalent
     */
    public function create(
        AutomaticDraftTalentCreationPayload $payload,
        Principal $requestedBy,
    ): DraftTalent;
}
