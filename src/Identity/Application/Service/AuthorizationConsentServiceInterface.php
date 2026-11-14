<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Domain\Entity\AuthorizationConnection;

/** Trusted consent boundary: caller authenticates the Identity and validates current Account grants. */
interface AuthorizationConsentServiceInterface
{
    /** @throws InvalidAuthorizationConsentException */
    public function createConnection(AuthorizationConnection $authorizationConnection): void;

    /**
     * @return string Validated client redirect URL containing the authorization code and state.
     * @throws InvalidAuthorizationConsentException
     */
    public function issueCode(string $connectionIdentifier, AuthorizationCodeParameters $authorizationCodeParameters): string;
}
