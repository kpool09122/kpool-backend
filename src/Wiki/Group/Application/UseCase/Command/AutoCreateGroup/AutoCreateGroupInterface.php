<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface AutoCreateGroupInterface
{
    /**
     * @param AutoCreateGroupInputPort $input
     * @return DraftGroup
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateGroupInputPort $input): DraftGroup;
}
