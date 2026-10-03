<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
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

    public function findSystemByName(string $name): ?Role;

    public function findByAccountIdAndName(AccountIdentifier $accountIdentifier, string $name): ?Role;

    public function delete(Role $role): void;
}
