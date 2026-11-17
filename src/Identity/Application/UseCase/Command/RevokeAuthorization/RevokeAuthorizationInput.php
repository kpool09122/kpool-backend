<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class RevokeAuthorizationInput implements RevokeAuthorizationInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier, private AuthorizationConnectionIdentifier $connectionIdentifier)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function connectionIdentifier(): AuthorizationConnectionIdentifier
    {
        return $this->connectionIdentifier;
    }
}
