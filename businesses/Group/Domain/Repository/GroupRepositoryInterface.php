<?php

namespace Businesses\Group\Domain\Repository;

use Businesses\Group\Domain\Entity\Group;
use Businesses\Group\Domain\ValueObject\GroupIdentifier;

interface GroupRepositoryInterface
{
    public function findById(GroupIdentifier $groupIdentifier): ?Group;

    public function save(Group $group): void;
}
