<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface TranslateAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function publishedAgencyIdentifier(): ?AgencyIdentifier;
}
