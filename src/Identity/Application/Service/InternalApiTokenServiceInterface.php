<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use SensitiveParameter;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;
use Source\Identity\Application\Exception\InvalidInternalApiTokenConfigurationException;

interface InternalApiTokenServiceInterface
{
    /**
     * Server-side only. Identity, client, accounts and scopes come exclusively from the verified MCP token.
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function issue(#[SensitiveParameter] string $mcpBearerToken): InternalApiToken;

    /**
     * @throws InvalidAuthorizationTokenException
     * @throws InvalidInternalApiTokenConfigurationException
     */
    public function verify(#[SensitiveParameter] string $apiBearerToken): ApiAuthenticationContext;

    /** Call after committing a change to the authorization for this source token. */
    public function forgetCachedAuthorization(string $sourceTokenIdentifier): void;
}
