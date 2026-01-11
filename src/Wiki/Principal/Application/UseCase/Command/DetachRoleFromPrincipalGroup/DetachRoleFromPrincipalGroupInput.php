<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DetachRoleFromPrincipalGroup;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

readonly class DetachRoleFromPrincipalGroupInput implements DetachRoleFromPrincipalGroupInputPort
{
    public function __construct(
        private PrincipalGroupIdentifier $principalGroupIdentifier,
        private RoleIdentifier $roleIdentifier,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }

    public function roleIdentifier(): RoleIdentifier
    {
        return $this->roleIdentifier;
    }
}
