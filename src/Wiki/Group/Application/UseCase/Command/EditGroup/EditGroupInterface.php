<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\EditGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface EditGroupInterface
{
    /**
     * @param EditGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditGroupInputPort $input): DraftGroup;
}
