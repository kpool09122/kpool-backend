<?php

namespace Businesses\Wiki\Group\UseCase\Query\GetGroup;

use Businesses\Wiki\Member\UseCase\Query\MemberReadModel;

interface GetGroupInterface
{
    public function process(GetGroupInputPort $input): MemberReadModel;
}
