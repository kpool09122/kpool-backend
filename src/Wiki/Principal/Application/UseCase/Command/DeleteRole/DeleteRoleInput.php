<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteRole;

use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

readonly class DeleteRoleInput implements DeleteRoleInputPort
{
    public function __construct(
        private RoleIdentifier $roleIdentifier,
    ) {
    }

    public function roleIdentifier(): RoleIdentifier
    {
        return $this->roleIdentifier;
    }
}
