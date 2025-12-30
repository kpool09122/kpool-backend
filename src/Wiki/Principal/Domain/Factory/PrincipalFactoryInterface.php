<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;

interface PrincipalFactoryInterface
{
    public function create(
        IdentityIdentifier $identityIdentifier,
    ): Principal;
}
