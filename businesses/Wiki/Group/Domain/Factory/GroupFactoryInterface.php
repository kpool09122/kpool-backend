<?php

namespace Businesses\Wiki\Group\Domain\Factory;

use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;

interface GroupFactoryInterface
{
    /**
     * @param GroupName $name
     * @return Group
     */
    public function create(
        GroupName          $name,
    ): Group;
}
