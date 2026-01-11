<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole;

use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

interface DetachPolicyFromRoleInputPort
{
    public function roleIdentifier(): RoleIdentifier;

    public function policyIdentifier(): PolicyIdentifier;
}
