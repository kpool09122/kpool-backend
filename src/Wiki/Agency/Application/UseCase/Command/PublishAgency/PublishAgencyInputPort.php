<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\PublishAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface PublishAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function publishedAgencyIdentifier(): ?AgencyIdentifier;
}
