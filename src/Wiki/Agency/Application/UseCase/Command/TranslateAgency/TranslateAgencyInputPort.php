<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface TranslateAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function publishedAgencyIdentifier(): ?AgencyIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
