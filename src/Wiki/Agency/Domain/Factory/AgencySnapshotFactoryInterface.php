<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;

interface AgencySnapshotFactoryInterface
{
    public function create(Agency $agency): AgencySnapshot;
}
