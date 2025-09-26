<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\RejectUpdatedGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface RejectUpdatedGroupInterface
{
    /**
     * @param RejectUpdatedGroupInputPort $input
     * @return DraftGroup
     * @throws GroupNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     */
    public function process(RejectUpdatedGroupInputPort $input): DraftGroup;
}
