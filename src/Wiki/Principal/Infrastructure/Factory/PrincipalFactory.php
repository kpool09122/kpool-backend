<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Domain\Entity\Principal;
use Source\Wiki\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class PrincipalFactory implements PrincipalFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        IdentityIdentifier $identityIdentifier,
        AccountIdentifier $accountIdentifier,
    ): Principal {
        return new Principal(
            new PrincipalIdentifier($this->uuidGenerator->generate()),
            $identityIdentifier,
            $accountIdentifier,
            null,
            true,
        );
    }

    public function createDelegatedPrincipal(
        Principal $originalPrincipal,
        DelegationIdentifier $delegationIdentifier,
        IdentityIdentifier $delegatedIdentityIdentifier,
    ): Principal {
        return new Principal(
            new PrincipalIdentifier($this->uuidGenerator->generate()),
            $delegatedIdentityIdentifier,
            $originalPrincipal->accountIdentifier(),
            $delegationIdentifier,
            true,
        );
    }
}
