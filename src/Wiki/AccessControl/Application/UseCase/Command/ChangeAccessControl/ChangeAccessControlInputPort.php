<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

interface ChangeAccessControlInputPort
{
    public function holdingRole(): Role;

    public function principalIdentifier(): PrincipalIdentifier;

    public function targetRole(): Role;
}
