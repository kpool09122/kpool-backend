<?php

namespace Businesses\Wiki\Agency\UseCase\Command\CreateAgency;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyName;
use Businesses\Wiki\Agency\Domain\ValueObject\CEO;
use Businesses\Wiki\Agency\Domain\ValueObject\Description;
use Businesses\Wiki\Agency\Domain\ValueObject\FoundedIn;

interface CreateAgencyInputPort
{
    public function translation(): Translation;

    public function name(): AgencyName;

    public function CEO(): CEO;

    public function foundedIn(): ?FoundedIn;

    public function description(): Description;
}
