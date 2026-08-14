<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface UpdatePrincipalGroupMembersInputPort
{
    public function accountIdentifier(): AccountIdentifier;

    public function principal(): Principal;

    public function accountType(): ?AccountType;

    /**
     * @return array<int, PrincipalGroupMembers>
     */
    public function principalGroups(): array;
}
