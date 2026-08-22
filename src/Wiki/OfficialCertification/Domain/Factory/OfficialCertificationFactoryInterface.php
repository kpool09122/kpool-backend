<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Factory;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\OfficialCertification\Domain\Entity\OfficialCertification;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface OfficialCertificationFactoryInterface
{
    public function create(
        ResourceType      $resourceType,
        TranslationSetIdentifier $translationSetIdentifier,
        AccountIdentifier $ownerAccountIdentifier,
    ): OfficialCertification;
}
