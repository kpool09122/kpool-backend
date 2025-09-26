<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveUpdatedAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

readonly class ApproveUpdatedAgencyInput implements ApproveUpdatedAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier  $agencyIdentifier,
        private ?AgencyIdentifier $publishedAgencyIdentifier,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function publishedAgencyIdentifier(): ?AgencyIdentifier
    {
        return $this->publishedAgencyIdentifier;
    }
}
