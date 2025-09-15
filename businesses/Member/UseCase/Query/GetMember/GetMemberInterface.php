<?php

namespace Businesses\Member\UseCase\Query\GetMember;

use Businesses\Member\UseCase\Query\MemberReadModel;

interface GetMemberInterface
{
    public function process(GetMemberInputPort $input): MemberReadModel;
}
