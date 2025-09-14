<?php

namespace Businesses\Member\UseCase\Query\GetMember;

interface GetMemberInterface
{
    public function process(GetMemberInputPort $input): MemberReadModel;
}
