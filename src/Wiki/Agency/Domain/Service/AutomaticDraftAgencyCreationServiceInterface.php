<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Service;

use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AutomaticDraftAgencyCreationPayload;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface AutomaticDraftAgencyCreationServiceInterface
{
    /**
     * @param AutomaticDraftAgencyCreationPayload $payload
     * @param Principal $requestedBy
     * @return DraftAgency
     */
    public function create(
        AutomaticDraftAgencyCreationPayload $payload,
        Principal $requestedBy,
    ): DraftAgency;
}
