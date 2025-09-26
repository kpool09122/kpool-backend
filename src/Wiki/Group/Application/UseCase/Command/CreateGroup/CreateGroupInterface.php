<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;

interface CreateGroupInterface
{
    public function process(CreateGroupInputPort $input): DraftGroup;
}
