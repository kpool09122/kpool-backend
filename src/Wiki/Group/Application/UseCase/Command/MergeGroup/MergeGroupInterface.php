<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\MergeGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;

interface MergeGroupInterface
{
    /**
     * @param MergeGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeGroupInputPort $input): DraftGroup;
}
