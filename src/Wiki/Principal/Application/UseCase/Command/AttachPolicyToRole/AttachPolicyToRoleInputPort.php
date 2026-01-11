<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AttachPolicyToRole;

use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

interface AttachPolicyToRoleInputPort
{
    public function roleIdentifier(): RoleIdentifier;

    public function policyIdentifier(): PolicyIdentifier;
}
