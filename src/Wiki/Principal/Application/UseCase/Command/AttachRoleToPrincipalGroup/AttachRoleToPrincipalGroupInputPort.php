<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\AttachRoleToPrincipalGroup;

use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;

interface AttachRoleToPrincipalGroupInputPort
{
    public function principalGroupIdentifier(): PrincipalGroupIdentifier;

    public function roleIdentifier(): RoleIdentifier;
}
