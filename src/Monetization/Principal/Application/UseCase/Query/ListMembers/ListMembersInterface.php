<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query\ListMembers;

use Source\Monetization\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

interface ListMembersInterface
{
    /**
     * @return array<int, PrincipalGroupMemberReadModel>
     * @throws UnauthorizedException
     */
    public function process(ListMembersInputPort $input): array;
}
