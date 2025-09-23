<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Domain\Entity\Agency;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface AgencyServiceInterface
{
    public function existsApprovedButNotTranslatedAgency(
        AgencyIdentifier $agencyIdentifier,
        AgencyIdentifier $publishedAgencyIdentifier,
    ): bool;

    public function translateAgency(
        Agency $agency,
        Translation $translation,
    ): DraftAgency;
}
