<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgencyForEdit;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface GetAgencyForEditInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function language(): Language;
}
