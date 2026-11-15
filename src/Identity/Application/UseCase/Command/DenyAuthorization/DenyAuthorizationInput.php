<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DenyAuthorization;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class DenyAuthorizationInput implements DenyAuthorizationInputPort
{
    public function __construct(private string $requestIdentifier, private IdentityIdentifier $identityIdentifier)
    {
    }

    public function requestIdentifier(): string
    {
        return $this->requestIdentifier;
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
