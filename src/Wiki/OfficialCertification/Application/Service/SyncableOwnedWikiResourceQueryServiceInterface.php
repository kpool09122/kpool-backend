<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Service;

use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface SyncableOwnedWikiResourceQueryServiceInterface
{
    /** @return SyncableOwnedWikiResource[] */
    public function findSyncableResources(AccountIdentifier $accountIdentifier): array;

    /**
     * @param SyncableOwnedWikiResource[] $resources
     * @return SyncableOwnedWikiResource[]
     */
    public function findOfficialResources(AccountIdentifier $accountIdentifier, array $resources): array;
}
