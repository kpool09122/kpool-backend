<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\SubmitUpdatedAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface SubmitUpdatedAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;
}
