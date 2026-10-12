<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupListReadModel;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface ListPrincipalGroupsInterface
{
    /** @throws UnauthorizedException */
    public function process(ListPrincipalGroupsInputPort $input): PrincipalGroupListReadModel;
}
