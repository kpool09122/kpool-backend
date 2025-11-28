<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Query\GetAgencyForEdit;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

readonly class GetAgencyForEditInput implements GetAgencyForEditInputPort
{
    public function __construct(
        private AgencyIdentifier $agencyIdentifier,
        private Language         $language,
    ) {
    }

    public function agencyIdentifier(): AgencyIdentifier
    {
        return $this->agencyIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
