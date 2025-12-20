<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Wiki\Group\Domain\Entity\GroupHistory;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

interface GroupHistoryFactoryInterface
{
    public function create(
        EditorIdentifier $editorIdentifier,
        ?EditorIdentifier $submitterIdentifier,
        ?GroupIdentifier $groupIdentifier,
        ?GroupIdentifier $draftGroupIdentifier,
        ?ApprovalStatus $fromStatus,
        ApprovalStatus $toStatus,
    ): GroupHistory;
}
