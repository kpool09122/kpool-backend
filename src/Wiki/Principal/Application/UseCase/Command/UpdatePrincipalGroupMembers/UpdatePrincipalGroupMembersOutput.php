<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeInterface;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

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

    /** @return array{principalStatuses: array<int, array{principalIdentifier: string, status: string}>, principalGroups: array<int, array{principalGroupIdentifier: string, accountIdentifier: string, name: string, description: string|null, isDefault: bool, memberCount: int, createdAt: string}>} */
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
                'isDefault' => $principalGroup->isDefault(),
                'memberCount' => $principalGroup->memberCount(),
                'createdAt' => $principalGroup->createdAt()->format(DateTimeInterface::ATOM),
            ], $this->principalGroups),
        ];
    }
}
