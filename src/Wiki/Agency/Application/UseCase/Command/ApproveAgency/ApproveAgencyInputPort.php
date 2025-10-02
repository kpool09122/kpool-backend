<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface ApproveAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function publishedAgencyIdentifier(): ?AgencyIdentifier;
}
