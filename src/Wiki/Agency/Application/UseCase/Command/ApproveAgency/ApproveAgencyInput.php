<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ApproveAgencyInput implements ApproveAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier    $agencyIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
