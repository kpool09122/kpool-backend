<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface AutoCreateGroupInterface
{
    /**
     * @param AutoCreateGroupInputPort $input
     * @return DraftGroup
     * @throws DisallowedException
     * @throws PrincipalNotFoundException
     */
    public function process(AutoCreateGroupInputPort $input): DraftGroup;
}
