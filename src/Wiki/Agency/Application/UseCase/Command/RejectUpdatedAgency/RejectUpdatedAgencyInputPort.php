<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectUpdatedAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface RejectUpdatedAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;
}
