<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Event;

use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConsentRevoked
{
    public function __construct(public IdentityIdentifier $identityIdentifier, public AuthorizationConsentIdentifier $consentIdentifier)
    {
    }
}
