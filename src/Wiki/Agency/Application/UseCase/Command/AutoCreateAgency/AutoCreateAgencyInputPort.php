<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\AutoCreateAgency;

use Source\Wiki\Agency\Domain\ValueObject\AutoAgencyCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface AutoCreateAgencyInputPort
{
    public function payload(): AutoAgencyCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
