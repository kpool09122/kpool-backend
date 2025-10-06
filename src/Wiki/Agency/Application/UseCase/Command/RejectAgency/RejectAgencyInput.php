<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\RejectAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

readonly class RejectAgencyInput implements RejectAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier $agencyIdentifier,
        private Principal $principal,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
