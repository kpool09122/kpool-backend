<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;

interface UpdatePrincipalGroupMembersOutputPort
{
    /** @param array<int, Principal> $principals */
    public function setPrincipalStatuses(array $principals): void;

    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void;
}
