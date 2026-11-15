<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class ApproveAuthorizationInput implements ApproveAuthorizationInputPort
{
    /** @param list<AuthorizationScope> $scopes
     * @param list<AuthorizationTarget> $targets */
    public function __construct(private string $requestIdentifier, private IdentityIdentifier $identityIdentifier, private array $scopes, private array $targets, private AccountIdentifier $initialAccountIdentifier)
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

    /** @return list<AuthorizationTarget> */
    public function targets(): array
    {
        return $this->targets;
    }

    public function initialAccountIdentifier(): AccountIdentifier
    {
        return $this->initialAccountIdentifier;
    }
}
