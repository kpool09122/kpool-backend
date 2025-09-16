<?php

namespace Businesses\Wiki\Agency\Domain\Factory;

use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;

interface AgencyFactoryInterface
{
    public function create(
        AgencyName $agencyName,
    ): Agency;
}
