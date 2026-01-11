<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Factory;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\OfficialCertification\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface OfficialCertificationFactoryInterface
{
    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
        AccountIdentifier $ownerAccountIdentifier,
    ): OfficialCertification;
}
