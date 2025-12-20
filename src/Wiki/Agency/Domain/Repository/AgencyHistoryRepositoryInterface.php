<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Repository;

use Source\Wiki\Agency\Domain\Entity\AgencyHistory;

interface AgencyHistoryRepositoryInterface
{
    public function save(AgencyHistory $agencyHistory): void;
}
