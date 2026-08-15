<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Wiki\Principal\Application\UseCase\Query\PrincipalGroupReadModel;

interface ListPrincipalGroupsInterface
{
    /** @return array<int, PrincipalGroupReadModel> */
    public function process(ListPrincipalGroupsInputPort $input): array;
}
