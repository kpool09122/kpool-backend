<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

use Source\Wiki\Principal\Domain\Entity\Role;

interface CreateRoleOutputPort
{
    public function setRole(Role $role): void;

    /**
     * @return array{roleIdentifier: ?string, name: ?string, isSystemRole: ?bool, createdAt: ?string}
     */
    public function toArray(): array;
}
