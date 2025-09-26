<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgency;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

class GetAgencyInput implements GetAgencyInputPort
{
    public function __construct(
        private AgencyIdentifier $agencyIdentifier,
        private Translation      $translation,
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
