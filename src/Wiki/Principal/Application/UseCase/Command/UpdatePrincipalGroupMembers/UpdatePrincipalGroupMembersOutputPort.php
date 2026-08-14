<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

interface UpdatePrincipalGroupMembersOutputPort
{
    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void;
}
