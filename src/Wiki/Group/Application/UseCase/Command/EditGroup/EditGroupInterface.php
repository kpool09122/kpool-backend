<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\Group;

interface EditGroupInterface
{
    /**
     * @param EditGroupInputPort $input
     * @return Group
     * @throws GroupNotFoundException
     */
    public function process(EditGroupInputPort $input): Group;
}
