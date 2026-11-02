<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Repository;

use Source\Analyze\Principal\Domain\Entity\Role;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findSystemByName(string $name): ?Role;

    /** @param RoleIdentifier[] $identifiers
     * @return Role[] */
    public function findByIds(array $identifiers): array;
}
