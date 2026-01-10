<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole;

use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;
use Source\Wiki\Principal\Application\Exception\RoleNotFoundException;

interface AttachPolicyToRoleInterface
{
    /**
     * @throws RoleNotFoundException
     * @throws PolicyNotFoundException
     */
    public function process(AttachPolicyToRoleInputPort $input): void;
}
