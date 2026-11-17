<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateAuthorizationScopeSettings;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface UpdateAuthorizationScopeSettingsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    /** @return list<AuthorizationScope> */
    public function scopes(): array;
}
