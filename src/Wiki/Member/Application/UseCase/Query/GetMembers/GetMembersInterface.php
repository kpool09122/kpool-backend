<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Application\UseCase\Query\GetMembers;

use Source\Wiki\Member\Application\UseCase\Query\MemberReadModel;

interface GetMembersInterface
{
    /**
     * @param GetMembersInputPort $input
     * @return list<MemberReadModel>
     */
    public function process(GetMembersInputPort $input): array;
}
