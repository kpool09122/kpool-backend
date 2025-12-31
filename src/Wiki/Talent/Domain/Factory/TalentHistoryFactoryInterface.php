<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

interface TalentHistoryFactoryInterface
{
    public function create(
        PrincipalIdentifier $editorIdentifier,
        ?PrincipalIdentifier $submitterIdentifier,
        ?TalentIdentifier $talentIdentifier,
        ?TalentIdentifier $draftTalentIdentifier,
        ?ApprovalStatus $fromStatus,
        ?ApprovalStatus $toStatus,
        TalentName $subjectName,
    ): TalentHistory;
}
