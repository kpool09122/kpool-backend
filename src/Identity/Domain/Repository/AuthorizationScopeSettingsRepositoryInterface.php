<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationScopeSettingsRepositoryInterface
{
    public function find(IdentityIdentifier $identityIdentifier): ?AuthorizationScopeSettings;

    public function save(AuthorizationScopeSettings $authorizationScopeSettings): void;
}
