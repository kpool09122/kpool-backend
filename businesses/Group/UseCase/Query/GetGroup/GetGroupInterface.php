<?php

namespace Businesses\Group\UseCase\Query\GetGroup;

use Businesses\Member\UseCase\Query\MemberReadModel;

interface GetGroupInterface
{
    public function process(GetGroupInputPort $input): MemberReadModel;
}
