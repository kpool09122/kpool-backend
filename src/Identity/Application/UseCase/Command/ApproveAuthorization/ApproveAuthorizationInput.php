<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ApproveAuthorizationInput implements ApproveAuthorizationInputPort
{
    /** @param list<AuthorizationScope> $scopes */
    public function __construct(private string $requestIdentifier, private IdentityIdentifier $identityIdentifier, private array $scopes)
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

    /** @return list<AuthorizationScope> */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
