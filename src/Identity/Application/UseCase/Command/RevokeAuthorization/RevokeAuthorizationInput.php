<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class RevokeAuthorizationInput implements RevokeAuthorizationInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier, private AuthorizationConsentIdentifier $consentIdentifier)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function consentIdentifier(): AuthorizationConsentIdentifier
    {
        return $this->consentIdentifier;
    }
}
