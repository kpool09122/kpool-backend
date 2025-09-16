<?php

namespace Businesses\Wiki\Group\UseCase\Command\CreateGroup;

use Businesses\Wiki\Group\Domain\Entity\Group;

interface CreateGroupInterface
{
    public function process(CreateGroupInputPort $input): Group;
}
