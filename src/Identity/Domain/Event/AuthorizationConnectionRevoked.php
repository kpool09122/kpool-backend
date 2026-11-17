<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConnectionRevoked
{
    public function __construct(public IdentityIdentifier $identityIdentifier, public AuthorizationConnectionIdentifier $connectionIdentifier)
    {
    }
}
