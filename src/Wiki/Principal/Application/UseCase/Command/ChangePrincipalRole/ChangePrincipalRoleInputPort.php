<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\ChangePrincipalRole;

use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ChangePrincipalRoleInputPort
{
    public function operatorIdentifier(): PrincipalIdentifier;

    public function targetPrincipalIdentifier(): PrincipalIdentifier;

    public function targetRole(): Role;
}
