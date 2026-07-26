<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class UpdatePrincipalGroupMembersInput implements UpdatePrincipalGroupMembersInputPort
{
    /**
     * @param array<int, PrincipalGroupMembers> $principalGroups
     */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private array $principalGroups,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }

    /**
     * @return array<int, PrincipalGroupMembers>
     */
    public function principalGroups(): array
    {
        return $this->principalGroups;
    }
}
