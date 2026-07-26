<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Account\Account\Application\Exception\AccountUpdateForbiddenException;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupReadModel;

interface ListPrincipalGroupsInterface
{
    /** @return array<int, PrincipalGroupReadModel> @throws AccountUpdateForbiddenException */
    public function process(ListPrincipalGroupsInputPort $input): array;
}
