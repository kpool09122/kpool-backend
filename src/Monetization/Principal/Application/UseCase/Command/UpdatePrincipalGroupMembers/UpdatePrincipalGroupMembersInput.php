<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class UpdatePrincipalGroupMembersInput implements UpdatePrincipalGroupMembersInputPort
{
    /** @param array<int, PrincipalGroupMembers> $principalGroups
     * @param array<int, PrincipalStatusUpdate> $principalStatuses */
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private PrincipalIdentifier $operatorPrincipalIdentifier,
        private array $principalGroups,
        private array $principalStatuses = [],
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

    /** @return array<int, PrincipalStatusUpdate> */
    public function principalStatuses(): array
    {
        return $this->principalStatuses;
    }
}
