<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

readonly class ChangeAccessControlInput implements ChangeAccessControlInputPort
{
    public function __construct(
        private Role                $holdingRole,
        private PrincipalIdentifier $principalIdentifier,
        private Role                $targetRole,
    ) {
    }

    public function holdingRole(): Role
    {
        return $this->holdingRole;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function targetRole(): Role
    {
        return $this->targetRole;
    }
}
