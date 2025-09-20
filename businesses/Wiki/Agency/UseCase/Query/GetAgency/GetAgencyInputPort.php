<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface GetAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function translation(): Translation;
}
