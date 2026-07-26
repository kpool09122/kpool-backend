<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;

class UpdatePrincipalGroupMembersOutput implements UpdatePrincipalGroupMembersOutputPort
{
    /** @var array<int, PrincipalGroup> */
    private array $principalGroups = [];

    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void
    {
        $this->principalGroups = $principalGroups;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'principalGroups' => array_map(static fn (PrincipalGroup $principalGroup): array => [
                'principalGroupIdentifier' => (string) $principalGroup->principalGroupIdentifier(),
                'accountIdentifier' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'roleIdentifiers' => array_map(static fn ($roleIdentifier): string => (string) $roleIdentifier, $principalGroup->roles()),
                'isDefault' => $principalGroup->isDefault(),
                'members' => array_map(
                    static fn (PrincipalIdentifier $principalIdentifier): string => (string) $principalIdentifier,
                    array_values($principalGroup->members()),
                ),
            ], $this->principalGroups),
        ];
    }
}
