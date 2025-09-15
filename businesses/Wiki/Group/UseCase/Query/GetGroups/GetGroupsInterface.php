<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroups;

use Businesses\Wiki\Group\UseCase\Query\GroupReadModel;

interface GetGroupsInterface
{
    /**
     * @param GetGroupsInputPort $input
     * @return list<GroupReadModel>
     */
    public function process(GetGroupsInputPort $input): array;
}
