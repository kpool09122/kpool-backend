<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Source\Identity\Domain\Entity\Identity;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Throwable;

interface AuthServiceInterface
{
    public function login(Identity $identity): Identity;

    /** @throws Throwable */
    public function logout(): void;

    public function isLoggedIn(): bool;

    public function refreshAuthenticatedIdentity(Identity $identity): void;

    /** @throws Throwable */
    public function invalidateAllSessions(IdentityIdentifier $identityIdentifier): void;

    public function isCurrentSessionValid(IdentityIdentifier $identityIdentifier): bool;
}
