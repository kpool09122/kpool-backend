<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class RequestCertificationInput implements RequestCertificationInputPort
{
    public function __construct(
        private ResourceType $resourceType,
        private ResourceIdentifier $resourceIdentifier,
        private AccountIdentifier $ownerAccountIdentifier,
    ) {
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function resourceIdentifier(): ResourceIdentifier
    {
        return $this->resourceIdentifier;
    }

    public function ownerAccountIdentifier(): AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }
}
