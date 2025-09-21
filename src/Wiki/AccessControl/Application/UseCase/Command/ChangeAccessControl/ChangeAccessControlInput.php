<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Application\UseCase\Command\ChangeAccessControl;

use Source\Wiki\Shared\Domain\ValueObject\ActorIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;

readonly class ChangeAccessControlInput implements ChangeAccessControlInputPort
{
    public function __construct(
        private Role $holdingRole,
        private ActorIdentifier $actorIdentifier,
        private Role $targetRole,
    ) {
    }

    public function holdingRole(): Role
    {
        return $this->holdingRole;
    }

    public function actorIdentifier(): ActorIdentifier
    {
        return $this->actorIdentifier;
    }

    public function targetRole(): Role
    {
        return $this->targetRole;
    }
}
