<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Service;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Principal\Domain\Entity\Principal;

interface AutomaticDraftGroupCreationServiceInterface
{
    /**
     * @param AutomaticDraftGroupCreationPayload $payload
     * @param Principal $requestedBy
     * @return DraftGroup
     */
    public function create(
        AutomaticDraftGroupCreationPayload $payload,
        Principal $requestedBy,
    ): DraftGroup;
}
