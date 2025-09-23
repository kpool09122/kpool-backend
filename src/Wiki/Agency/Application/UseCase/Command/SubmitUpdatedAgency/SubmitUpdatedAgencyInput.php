<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\SubmitUpdatedAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

readonly class SubmitUpdatedAgencyInput implements SubmitUpdatedAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier    $agencyIdentifier,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }
}
