<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface RequestCertificationInputPort
{
    public function resourceType(): ResourceType;

    public function resourceIdentifier(): ResourceIdentifier;

    public function ownerAccountIdentifier(): AccountIdentifier;
}
