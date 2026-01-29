<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency;

use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class AutoCreateAgencyInput implements AutoCreateAgencyInputPort
{
    public function __construct(
        private AutoAgencyCreationPayload $payload,
        private PrincipalIdentifier       $principalIdentifier,
    ) {
    }

    public function payload(): AutoAgencyCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
