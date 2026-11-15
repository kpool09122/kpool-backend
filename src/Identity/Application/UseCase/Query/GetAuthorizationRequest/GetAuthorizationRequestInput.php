<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationRequest;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class GetAuthorizationRequestInput implements GetAuthorizationRequestInputPort
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
