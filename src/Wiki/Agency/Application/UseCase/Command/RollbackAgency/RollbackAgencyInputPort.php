<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

interface RollbackAgencyInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function agencyIdentifier(): AgencyIdentifier;

    public function targetVersion(): Version;
}
