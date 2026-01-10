<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup;

use Source\Wiki\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;

interface AttachRoleToPrincipalGroupInterface
{
    /**
     * @throws PrincipalGroupNotFoundException
     * @throws RoleNotFoundException
     */
    public function process(AttachRoleToPrincipalGroupInputPort $input): void;
}
