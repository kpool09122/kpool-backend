<?php

namespace Businesses\Wiki\Member\UseCase\Query\GetMember;

use Businesses\Wiki\Member\UseCase\Query\MemberReadModel;

interface GetMemberInterface
{
    public function process(GetMemberInputPort $input): MemberReadModel;
}
