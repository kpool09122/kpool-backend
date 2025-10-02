<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

readonly class RejectAgencyInput implements RejectAgencyInputPort
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
