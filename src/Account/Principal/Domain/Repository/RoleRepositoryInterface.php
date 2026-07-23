<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Repository;

use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\AccountRole;

interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findByRole(AccountRole $role): Role;

    /**
     * @param AccountRole[] $roles
     * @return array<string, Role> role value をキーとした連想配列
     */
    public function findByRoles(array $roles): array;
}
