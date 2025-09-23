<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectUpdatedAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

readonly class RejectUpdatedAgencyInput implements RejectUpdatedAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier $agencyIdentifier,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }
}
