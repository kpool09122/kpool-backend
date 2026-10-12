<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Command\UpdatePrincipalGroupMembers;

use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;

class UpdatePrincipalGroupMembersOutput implements UpdatePrincipalGroupMembersOutputPort
{
    /** @var array<int, PrincipalGroup> */
    private array $principalGroups = [];

    /** @param array<int, PrincipalGroup> $principalGroups */
    public function setPrincipalGroups(array $principalGroups): void
    {
        $this->principalGroups = $principalGroups;
    }

    /** @return array{principalGroups: array<int, array{principalGroupIdentifier: string, accountIdentifier: string, name: string, description: string|null, isDefault: bool, memberCount: int}>} */
    public function toArray(): array
    {
        return [
            'principalGroups' => array_map(static fn (PrincipalGroup $principalGroup): array => [
                'principalGroupIdentifier' => (string) $principalGroup->principalGroupIdentifier(),
                'accountIdentifier' => (string) $principalGroup->accountIdentifier(),
                'name' => $principalGroup->name(),
                'description' => $principalGroup->description() !== null ? (string) $principalGroup->description() : null,
                'isDefault' => $principalGroup->isDefault(),
                'memberCount' => count($principalGroup->members()),
            ], $this->principalGroups),
        ];
    }
}
