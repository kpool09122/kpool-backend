<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class UpdatePrincipalGroupMembersInput implements UpdatePrincipalGroupMembersInputPort
{
    /** @param array<int, PrincipalGroupMembers> $principalGroups */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private PrincipalIdentifier $operatorPrincipalIdentifier,
        private array $principalGroups,
        private AccountType $accountType,
    ) {
    }

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function operatorPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->operatorPrincipalIdentifier;
    }

    /** @return array<int, PrincipalGroupMembers> */
    public function principalGroups(): array
    {
        return $this->principalGroups;
    }

    public function accountType(): AccountType
    {
        return $this->accountType;
    }
}
