<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface GroupServiceInterface
{
    public function existsApprovedButNotTranslatedGroup(
        GroupIdentifier $groupIdentifier,
        GroupIdentifier $publishedGroupIdentifier,
    ): bool;

    public function translateGroup(
        Group  $group,
        Translation $translation,
    ): DraftGroup;
}
