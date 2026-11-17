<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;
use Source\Identity\Domain\ValueObject\AuthorizationConnectionIdentifier;

/** Issues authorization codes for a connection already validated and saved by the caller. */
interface AuthorizationConnectionServiceInterface
{
    /**
     * @return string Validated client redirect URL containing the authorization code and state.
     * @throws InvalidAuthorizationRequestException
     */
    public function issueCode(AuthorizationConnectionIdentifier $connectionIdentifier, AuthorizationCodeParameters $authorizationCodeParameters): string;
}
