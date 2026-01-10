<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeleteRole;

use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemRoleException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;

interface DeleteRoleInterface
{
    /**
     * @throws RoleNotFoundException
     * @throws CannotDeleteSystemRoleException
     */
    public function process(DeleteRoleInputPort $input): void;
}
