<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\Entity\GroupSnapshot;

interface GroupSnapshotFactoryInterface
{
    public function create(Group $group): GroupSnapshot;
}
