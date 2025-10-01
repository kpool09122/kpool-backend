<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\SubmitGroup;

use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface SubmitGroupInterface
{
    /**
     * @param SubmitGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     */
    public function process(SubmitGroupInputPort $input): DraftGroup;
}
