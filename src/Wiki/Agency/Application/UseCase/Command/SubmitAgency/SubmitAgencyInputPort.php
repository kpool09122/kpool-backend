<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\SubmitAgency;

use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;

interface SubmitAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function principal(): Principal;
}
