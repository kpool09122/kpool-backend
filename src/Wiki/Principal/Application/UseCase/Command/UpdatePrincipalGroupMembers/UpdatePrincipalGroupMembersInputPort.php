<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface UpdatePrincipalGroupMembersInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function operatorPrincipalIdentifier(): PrincipalIdentifier;

    /** @return array<int, PrincipalGroupMembers> */
    public function principalGroups(): array;

    public function accountType(): AccountType;
}
