<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Repository;

use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findById(RoleIdentifier $roleIdentifier): ?Role;

    /**
     * @param RoleIdentifier[] $roleIdentifiers
     * @return array<string, Role> roleIdentifier をキーとした連想配列
     */
    public function findByIds(array $roleIdentifiers): array;

    public function findByName(string $name): ?Role;
}
