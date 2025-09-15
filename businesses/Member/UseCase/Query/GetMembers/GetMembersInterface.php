<?php

namespace Businesses\Member\UseCase\Query\GetMembers;

use Businesses\Member\UseCase\Query\MemberReadModel;

interface GetMembersInterface
{
    /**
     * @param GetMembersInputPort $input
     * @return list<MemberReadModel>
     */
    public function process(GetMembersInputPort $input): array;
}
