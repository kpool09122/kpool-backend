<?php

namespace Businesses\Group\UseCase\Command\EditGroup;

use Businesses\Group\Domain\Entity\Group;
use Businesses\Group\UseCase\Exception\GroupNotFoundException;

interface EditGroupInterface
{
    /**
     * @param EditGroupInputPort $input
     * @return Group|null
     * @throws GroupNotFoundException
     */
    public function process(EditGroupInputPort $input): ?Group;
}
