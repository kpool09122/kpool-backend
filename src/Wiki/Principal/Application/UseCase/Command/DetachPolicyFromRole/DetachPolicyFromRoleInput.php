<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachPolicyFromRole;

use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

readonly class DetachPolicyFromRoleInput implements DetachPolicyFromRoleInputPort
{
    public function __construct(
        private RoleIdentifier $roleIdentifier,
        private PolicyIdentifier $policyIdentifier,
    ) {
    }

    public function roleIdentifier(): RoleIdentifier
    {
        return $this->roleIdentifier;
    }

    public function policyIdentifier(): PolicyIdentifier
    {
        return $this->policyIdentifier;
    }
}
