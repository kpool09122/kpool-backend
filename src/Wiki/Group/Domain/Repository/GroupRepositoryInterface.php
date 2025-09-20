<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Repository;

use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface GroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?Group;

    public function save(Group $group): void;
}
