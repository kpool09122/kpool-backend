<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\ListAuthorizationConnections;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ListAuthorizationConnectionsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
