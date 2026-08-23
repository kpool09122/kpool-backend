<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\UseCase\Command\SyncOwnedWikiCertifications;

use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface SyncOwnedWikiCertificationsInterface
{
    /**
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(SyncOwnedWikiCertificationsInputPort $input, SyncOwnedWikiCertificationsOutputPort $output): void;
}
