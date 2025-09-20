<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Query\GetMember;

use Source\Wiki\Member\Application\UseCase\Query\MemberReadModel;

interface GetMemberInterface
{
    public function process(GetMemberInputPort $input): MemberReadModel;
}
