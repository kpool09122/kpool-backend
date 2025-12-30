<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\CreateGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface CreateGroupInterface
{
    /**
     * @param CreateGroupInputPort $input
     * @return DraftGroup
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreateGroupInputPort $input): DraftGroup;
}
