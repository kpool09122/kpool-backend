<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;

interface AgencyFactoryInterface
{
    public function create(
        Translation $translation,
        AgencyName $agencyName,
    ): Agency;
}
