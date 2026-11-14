<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;

/** Issues authorization codes for consent already validated and saved by the caller. */
interface AuthorizationConsentServiceInterface
{
    /**
     * @return string Validated client redirect URL containing the authorization code and state.
     * @throws InvalidAuthorizationConsentException
     */
    public function issueCode(AuthorizationConsentIdentifier $consentIdentifier, AuthorizationCodeParameters $authorizationCodeParameters): string;
}
