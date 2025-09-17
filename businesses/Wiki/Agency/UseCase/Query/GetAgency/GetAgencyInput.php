<?php

namespace Businesses\Wiki\Agency\UseCase\Query\GetAgency;

use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

class GetAgencyInput implements GetAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier $agencyIdentifier,
        private Translation $translation,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
