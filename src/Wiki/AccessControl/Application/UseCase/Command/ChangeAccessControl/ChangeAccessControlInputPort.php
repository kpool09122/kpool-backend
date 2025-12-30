<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface ChangeAccessControlInputPort
{
    public function holdingRole(): Role;

    public function principalIdentifier(): PrincipalIdentifier;

    public function targetRole(): Role;
}
