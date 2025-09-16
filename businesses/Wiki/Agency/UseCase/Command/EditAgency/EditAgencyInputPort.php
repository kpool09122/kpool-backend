<?php

namespace Businesses\Wiki\Agency\UseCase\Command\EditAgency;

use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use Businesses\Wiki\Agency\Domain\ValueObject\Description;
use Businesses\Wiki\Agency\Domain\ValueObject\FoundedIn;

interface EditAgencyInputPort
{
    public function agencyIdentifier(): AgencyIdentifier;

    public function name(): AgencyName;

    public function CEO(): CEO;

    public function foundedIn(): ?FoundedIn;

    public function description(): Description;
}
