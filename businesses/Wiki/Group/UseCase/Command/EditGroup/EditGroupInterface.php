<?php

namespace Businesses\Wiki\Group\UseCase\Command\EditGroup;

use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\UseCase\Exception\GroupNotFoundException;

interface EditGroupInterface
{
    /**
     * @param EditGroupInputPort $input
     * @return Group|null
     * @throws GroupNotFoundException
     */
    public function process(EditGroupInputPort $input): ?Group;
}
