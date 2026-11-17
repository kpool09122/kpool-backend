<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class UpdateAuthorizationScopeSettingsInput implements UpdateAuthorizationScopeSettingsInputPort
{
    /** @param list<AuthorizationScope> $scopes */
    public function __construct(private IdentityIdentifier $identityIdentifier, private array $scopes)
    {
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
