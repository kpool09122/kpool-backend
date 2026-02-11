<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Factory;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface OfficialCertificationFactoryInterface
{
    public function create(
        ResourceType      $resourceType,
        WikiIdentifier    $wikiIdentifier,
        AccountIdentifier $ownerAccountIdentifier,
    ): OfficialCertification;
}
