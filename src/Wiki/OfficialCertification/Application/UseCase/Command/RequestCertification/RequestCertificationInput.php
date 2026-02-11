<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\RequestCertification;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class RequestCertificationInput implements RequestCertificationInputPort
{
    public function __construct(
        private ResourceType      $resourceType,
        private WikiIdentifier    $wikiIdentifier,
        private AccountIdentifier $ownerAccountIdentifier,
    ) {
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function ownerAccountIdentifier(): AccountIdentifier
    {
        return $this->ownerAccountIdentifier;
    }
}
