<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class GetAuthorizationScopeSettingsInput implements GetAuthorizationScopeSettingsInputPort
{
    public function __construct(private IdentityIdentifier $identityIdentifier)
    {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }
}
