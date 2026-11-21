<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups;

use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface ListPrincipalGroupsInterface
{
    /**
     * @return array<int, PrincipalGroupReadModel>
     * @throws UnauthorizedException
     */
    public function process(ListPrincipalGroupsInputPort $input): array;
}
