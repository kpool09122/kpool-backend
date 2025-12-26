<?php

declare(strict_types=1);

namespace Source\Wiki\AccessControl\Infrastructure\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\AccessControl\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Shared\Domain\Entity\Principal;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Role;
use Symfony\Component\Uid\Ulid;

readonly class PrincipalFactory implements PrincipalFactoryInterface
{
    public function create(
        IdentityIdentifier $identityIdentifier,
    ): Principal {
        return new Principal(
            new PrincipalIdentifier(Ulid::generate()),
            $identityIdentifier,
            Role::NONE,
            null,
            [],
            [],
        );
    }
}
