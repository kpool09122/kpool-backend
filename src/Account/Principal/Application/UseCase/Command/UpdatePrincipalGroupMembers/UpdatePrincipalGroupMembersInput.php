<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class UpdatePrincipalGroupMembersInput implements UpdatePrincipalGroupMembersInputPort
{
    /**
     * @param array<int, PrincipalGroupMembers> $principalGroups
     * @param array<int, PrincipalStatusUpdate> $principalStatuses
     */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private Principal $principal,
        private array $principalGroups,
        private ?AccountType $accountType = null,
        private array $principalStatuses = [],
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

    public function accountType(): ?AccountType
    {
        return $this->accountType;
    }

    /**
     * @return array<int, PrincipalGroupMembers>
     */
    public function principalGroups(): array
    {
        return $this->principalGroups;
    }

    /** @return array<int, PrincipalStatusUpdate> */
    public function principalStatuses(): array
    {
        return $this->principalStatuses;
    }
}
