<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Domain\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\Role;

interface PrincipalFactoryInterface
{
    public function create(
        IdentityIdentifier $identityIdentifier,
    ): Principal;
}
