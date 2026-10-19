<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;

class UpdatePrincipalGroupMembersOutput implements UpdatePrincipalGroupMembersOutputPort
{
    /** @var array<int, Principal> */
    private array $principalStatuses = [];

    /** @param array<int, Principal> $principals */
    public function setPrincipalStatuses(array $principals): void
    {
        $this->principalStatuses = $principals;
    }

    /** @var array<int, PrincipalGroup> */
    private array $principalGroups = [];

    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void
    {
        $this->principalGroups = $principalGroups;
    }

    /** @return array{principalStatuses: array<int, array{principalIdentifier: string, status: string}>, principalGroups: array<int, array{principalGroupIdentifier: string, accountIdentifier: string, name: string, description: string|null, roleIdentifiers: array<string>, isDefault: bool, members: list<string>}>} */
    public function toArray(): array
    {
        return [
            'principalStatuses' => array_map(static fn (Principal $principal): array => [
                'principalIdentifier' => (string) $principal->principalIdentifier(),
                'status' => $principal->status()->value,
            ], $this->principalStatuses),
            'principalGroups' => array_map(static fn (PrincipalGroup $principalGroup): array => [
                'principalGroupIdentifier' => (string) $principalGroup->principalGroupIdentifier(),
                'accountIdentifier' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'description' => $principalGroup->description() !== null ? (string) $principalGroup->description() : null,
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
