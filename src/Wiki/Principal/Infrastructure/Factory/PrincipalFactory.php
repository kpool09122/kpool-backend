<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Principal\Domain\ValueObject\Role;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
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
