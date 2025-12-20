<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\TalentHistory;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;

interface TalentHistoryFactoryInterface
{
    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?TalentIdentifier $talentIdentifier,
        ?TalentIdentifier $draftTalentIdentifier,
        ?ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): TalentHistory;
}
