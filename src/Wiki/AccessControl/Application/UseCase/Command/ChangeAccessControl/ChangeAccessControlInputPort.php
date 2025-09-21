<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\Shared\Domain\ValueObject\ActorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

interface ChangeAccessControlInputPort
{
    public function holdingRole(): Role;

    public function actorIdentifier(): ActorIdentifier;

    public function targetRole(): Role;
}
