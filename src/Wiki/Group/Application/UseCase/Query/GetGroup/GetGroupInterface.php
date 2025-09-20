<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Query\GetGroup;

use Source\Wiki\Member\Application\UseCase\Query\MemberReadModel;

interface GetGroupInterface
{
    public function process(GetGroupInputPort $input): MemberReadModel;
}
