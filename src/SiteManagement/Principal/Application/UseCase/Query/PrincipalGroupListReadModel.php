<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Application\UseCase\Query;

readonly class PrincipalGroupListReadModel
{
    /** @param array<int, PrincipalGroupReadModel> $principalGroups
     * @param array<int, PrincipalGroupMemberReadModel> $principals */
    public function __construct(private array $principalGroups, private array $principals)
    {
    }

    /** @return array<int, PrincipalGroupReadModel> */
    public function principalGroups(): array
    {
        return $this->principalGroups;
    }

    /** @return array<int, PrincipalGroupMemberReadModel> */
    public function principals(): array
    {
        return $this->principals;
    }

    /** @return array{principalGroups: array<int, array<string, mixed>>, principals: array<int, array<string, string>>} */
    public function toArray(): array
    {
        return ['principalGroups' => array_map(static fn (PrincipalGroupReadModel $group): array => $group->toArray(), $this->principalGroups),
         'principals' => array_map(static fn (PrincipalGroupMemberReadModel $principal): array => $principal->toArray(), $this->principals)];
    }
}
