<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole;

use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class ChangePrincipalRoleInput implements ChangePrincipalRoleInputPort
{
    public function __construct(
        private PrincipalIdentifier $operatorIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private Role                $targetRole,
    ) {
    }

    public function operatorIdentifier(): PrincipalIdentifier
    {
        return $this->operatorIdentifier;
    }

    public function targetPrincipalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function targetRole(): Role
    {
        return $this->targetRole;
    }
}
