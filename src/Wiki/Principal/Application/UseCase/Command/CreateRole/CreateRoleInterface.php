<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreateRole;

use Source\Wiki\Principal\Domain\Entity\Role;

interface CreateRoleInterface
{
    public function process(CreateRoleInputPort $input): Role;
}
