<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface RejectAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
