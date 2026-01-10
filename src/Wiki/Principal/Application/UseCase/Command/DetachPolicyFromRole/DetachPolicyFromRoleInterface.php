<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole;

use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;

interface DetachPolicyFromRoleInterface
{
    /**
     * @throws RoleNotFoundException
     */
    public function process(DetachPolicyFromRoleInputPort $input): void;
}
