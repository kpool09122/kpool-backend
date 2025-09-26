<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\PublishGroup;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Group\Application\Exception\GroupNotFoundException;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;

interface PublishGroupInterface
{
    /**
     * @param PublishGroupInputPort $input
     * @return Group
     * @throws GroupNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedGroupException
     */
    public function process(PublishGroupInputPort $input): Group;
}
