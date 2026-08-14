<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use DateTimeInterface;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;

class UpdatePrincipalGroupMembersOutput implements UpdatePrincipalGroupMembersOutputPort
{
    /** @var array<int, PrincipalGroup> */
    private array $principalGroups = [];

    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void
    {
        $this->principalGroups = $principalGroups;
    }

    /** @return array{principalGroups: array<int, array{principalGroupIdentifier: string, accountIdentifier: string, name: string, isDefault: bool, memberCount: int, createdAt: string}>} */
    public function toArray(): array
    {
        return [
            'principalGroups' => array_map(static fn (PrincipalGroup $principalGroup): array => [
                'principalGroupIdentifier' => (string) $principalGroup->principalGroupIdentifier(),
                'accountIdentifier' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'isDefault' => $principalGroup->isDefault(),
                'memberCount' => $principalGroup->memberCount(),
                'createdAt' => $principalGroup->createdAt()->format(DateTimeInterface::ATOM),
            ], $this->principalGroups),
        ];
    }
}
