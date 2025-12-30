<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Repository;

use Source\Wiki\Agency\Domain\Entity\AgencySnapshot;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

interface AgencySnapshotRepositoryInterface
{
    public function save(AgencySnapshot $snapshot): void;

    /**
     * @param AgencyIdentifier $agencyIdentifier
     * @return AgencySnapshot[]
     */
    public function findByAgencyIdentifier(AgencyIdentifier $agencyIdentifier): array;

    public function findByAgencyAndVersion(
        AgencyIdentifier $agencyIdentifier,
        Version $version
    ): ?AgencySnapshot;
}
