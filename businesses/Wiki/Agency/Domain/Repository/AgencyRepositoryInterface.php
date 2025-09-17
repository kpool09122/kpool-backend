<?php

declare(strict_types=1);

namespace Businesses\Wiki\Agency\Domain\Repository;

use Businesses\Wiki\Agency\Domain\Entity\Agency;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface AgencyRepositoryInterface
{
    public function findById(AgencyIdentifier $agencyIdentifier): ?Agency;

    public function save(Agency $agency): void;
}
