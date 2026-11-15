<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface AuthorizationRequestStorageServiceInterface
{
    public function store(AuthorizationCodeParameters $authorizationCodeParameters, IdentityIdentifier $identityIdentifier): PendingAuthorization;

    /** @throws AuthorizationRequestUnavailableException */
    public function requireValid(string $identifier, IdentityIdentifier $identityIdentifier): PendingAuthorization;

    /**
     * Validate now and mark completed after commit when a transaction is active.
     *
     * @throws AuthorizationRequestUnavailableException
     */
    public function complete(string $identifier, IdentityIdentifier $identityIdentifier): void;
}
