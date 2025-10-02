<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

readonly class ApproveAgencyInput implements ApproveAgencyInputPort
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
