<?php

namespace Businesses\Wiki\Agency\Domain\Factory;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;

interface AgencyFactoryInterface
{
    public function create(
        Translation $translation,
        AgencyName $agencyName,
    ): Agency;
}
