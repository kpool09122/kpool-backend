<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitUpdatedGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface SubmitUpdatedGroupInterface
{
    /**
     * @param SubmitUpdatedGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function process(SubmitUpdatedGroupInputPort $input): DraftGroup;
}
