<?php

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface GetAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;
}
