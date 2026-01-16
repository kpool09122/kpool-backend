<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface OfficialResourceUpdaterInterface
{
    public function markOfficial(
        ResourceType $type,
        ResourceIdentifier $id,
        AccountIdentifier $owner,
    ): void;
}
