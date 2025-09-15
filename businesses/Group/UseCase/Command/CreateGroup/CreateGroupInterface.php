<?php

namespace Businesses\Group\UseCase\Command\CreateGroup;

use Businesses\Group\Domain\Entity\Group;

interface CreateGroupInterface
{
    public function process(CreateGroupInputPort $input): ?Group;
}
