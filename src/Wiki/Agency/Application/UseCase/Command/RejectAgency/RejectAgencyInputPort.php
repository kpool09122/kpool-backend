<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface RejectAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;
}
