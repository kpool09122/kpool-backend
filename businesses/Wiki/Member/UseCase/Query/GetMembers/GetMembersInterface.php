<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\UseCase\Query\GetMembers;

use Businesses\Wiki\Member\UseCase\Query\MemberReadModel;

interface GetMembersInterface
{
    /**
     * @param GetMembersInputPort $input
     * @return list<MemberReadModel>
     */
    public function process(GetMembersInputPort $input): array;
}
