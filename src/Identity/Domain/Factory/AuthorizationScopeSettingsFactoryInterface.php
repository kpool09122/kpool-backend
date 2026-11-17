<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use InvalidArgumentException;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationScopeSettingsFactoryInterface
{
    /**
     * @param list<AuthorizationScope> $scopes
     * @throws InvalidArgumentException
     */
    public function create(IdentityIdentifier $identityIdentifier, array $scopes): AuthorizationScopeSettings;
}
