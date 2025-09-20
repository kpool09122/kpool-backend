<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgency;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface GetAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function translation(): Translation;
}
