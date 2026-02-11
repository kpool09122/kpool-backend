<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface RequestCertificationInputPort
{
    public function resourceType(): ResourceType;

    public function wikiIdentifier(): WikiIdentifier;

    public function ownerAccountIdentifier(): AccountIdentifier;
}
