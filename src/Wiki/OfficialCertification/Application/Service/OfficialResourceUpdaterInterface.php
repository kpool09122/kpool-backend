<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface OfficialResourceUpdaterInterface
{
    public function markOfficial(
        ResourceType $type,
        WikiIdentifier $id,
        AccountIdentifier $owner,
    ): void;
}
