<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface UpdatePrincipalGroupMembersInputPort
{
    /** @return array<int, PrincipalStatusUpdate> */
    public function principalStatuses(): array;

    public function accountIdentifier(): AccountIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;

    /** @return array<int, PrincipalGroupMembers> */
    public function principalGroups(): array;
}
