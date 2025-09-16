<?php

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

class GetAgencyInput implements GetAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier $agencyIdentifier
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }
}
