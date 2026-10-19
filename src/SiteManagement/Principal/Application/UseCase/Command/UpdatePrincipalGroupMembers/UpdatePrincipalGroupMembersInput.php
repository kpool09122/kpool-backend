<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

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
