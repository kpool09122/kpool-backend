<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class GetAuthenticatedIdentityInput implements GetAuthenticatedIdentityInputPort
{
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
