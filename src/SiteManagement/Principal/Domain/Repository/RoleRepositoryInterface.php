<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Repository;

use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findSystemByName(string $name): ?Role;

    /** @param RoleIdentifier[] $identifiers
     * @return Role[] */
    public function findByIds(array $identifiers): array;
}
