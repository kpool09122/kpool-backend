<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroups;

use Source\Wiki\Group\Application\UseCase\Query\GroupReadModel;

interface GetGroupsInterface
{
    /**
     * @param GetGroupsInputPort $input
     * @return list<GroupReadModel>
     */
    public function process(GetGroupsInputPort $input): array;
}
