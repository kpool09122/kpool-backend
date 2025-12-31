<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Factory;

use Source\Wiki\Agency\Domain\Entity\AgencyHistory;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface AgencyHistoryFactoryInterface
{
    public function create(
        PrincipalIdentifier $editorIdentifier,
        ?PrincipalIdentifier $submitterIdentifier,
        ?AgencyIdentifier $agencyIdentifier,
        ?AgencyIdentifier $draftAgencyIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        AgencyName $subjectName,
    ): AgencyHistory;
}
