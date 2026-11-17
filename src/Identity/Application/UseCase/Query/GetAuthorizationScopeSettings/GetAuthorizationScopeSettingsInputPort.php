<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface GetAuthorizationScopeSettingsInputPort
{
    public function identityIdentifier(): IdentityIdentifier;
}
