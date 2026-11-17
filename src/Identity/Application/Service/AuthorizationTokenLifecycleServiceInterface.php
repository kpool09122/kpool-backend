<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationTokenLifecycleServiceInterface
{
    public function revoke(AuthorizationConnectionIdentifier $connectionIdentifier): void;

    public function deleteForIdentity(IdentityIdentifier $identityIdentifier): void;
}
