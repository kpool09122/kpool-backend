<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\PrincipalGroup;

interface UpdatePrincipalGroupMembersOutputPort
{
    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void;
}
