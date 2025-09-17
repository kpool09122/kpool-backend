<?php

declare(strict_types=1);

namespace Businesses\Wiki\Group\Domain\Repository;

use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;

interface GroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?Group;

    public function save(Group $group): void;
}
