<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\ApproveUpdatedAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface ApproveUpdatedAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function publishedAgencyIdentifier(): ?AgencyIdentifier;
}
