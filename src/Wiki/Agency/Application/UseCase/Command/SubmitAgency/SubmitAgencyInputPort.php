<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface SubmitAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
