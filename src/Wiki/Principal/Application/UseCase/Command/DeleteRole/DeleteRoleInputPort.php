<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteRole;

use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

interface DeleteRoleInputPort
{
    public function roleIdentifier(): RoleIdentifier;
}
