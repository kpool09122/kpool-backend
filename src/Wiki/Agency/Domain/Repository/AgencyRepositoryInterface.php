<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Repository;

use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface AgencyRepositoryInterface
{
    public function findById(AgencyIdentifier $agencyIdentifier): ?Agency;

    public function save(Agency $agency): void;
}
