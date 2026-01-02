<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RollbackAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class RollbackAgencyInput implements RollbackAgencyInputPort
{
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private AgencyIdentifier $agencyIdentifier,
        private Version $targetVersion,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function targetVersion(): Version
    {
        return $this->targetVersion;
    }
}
