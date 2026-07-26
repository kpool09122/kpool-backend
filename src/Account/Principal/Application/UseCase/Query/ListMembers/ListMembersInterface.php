<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query\ListMembers;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\UseCase\Query\MemberReadModel;

interface ListMembersInterface
{
    /** @return array<int, MemberReadModel> @throws AccountUpdateForbiddenException */
    public function process(ListMembersInputPort $input): array;
}
