<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface UpdatePrincipalGroupMembersInputPort
{
    /** @return array<int, PrincipalStatusUpdate> */
    public function principalStatuses(): array;

    public function accountIdentifier(): AccountIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;

    /** @return array<int, PrincipalGroupMembers> */
    public function principalGroups(): array;
}
