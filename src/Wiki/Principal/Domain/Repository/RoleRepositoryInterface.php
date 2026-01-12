<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findById(RoleIdentifier $roleIdentifier): ?Role;

    /**
     * @param RoleIdentifier[] $roleIdentifiers
     * @return array<string, Role> roleIdentifier をキーとした連想配列
     */
    public function findByIds(array $roleIdentifiers): array;

    /**
     * @return array<Role>
     */
    public function findAll(): array;

    public function findByName(string $name): ?Role;

    public function delete(Role $role): void;
}
