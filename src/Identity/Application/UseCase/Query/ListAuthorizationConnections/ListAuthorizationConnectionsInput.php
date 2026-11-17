<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListAuthorizationConnections;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ListAuthorizationConnectionsInput implements ListAuthorizationConnectionsInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
