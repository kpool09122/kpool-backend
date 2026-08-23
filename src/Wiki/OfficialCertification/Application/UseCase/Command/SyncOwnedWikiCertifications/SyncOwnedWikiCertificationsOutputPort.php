<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Wiki\OfficialCertification\Application\Service\SyncableOwnedWikiResource;

interface SyncOwnedWikiCertificationsOutputPort
{
    /**
     * @param SyncableOwnedWikiResource[] $approved
     * @param SyncableOwnedWikiResource[] $rejected
     * @param SyncableOwnedWikiResource[] $unchanged
     */
    public function setResult(array $approved, array $rejected, array $unchanged): void;
}
