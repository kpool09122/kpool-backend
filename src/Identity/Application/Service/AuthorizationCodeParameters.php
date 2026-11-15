<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;

readonly class AuthorizationCodeParameters
{
    /** @param list<AuthorizationScope> $requestedScopes */
    public function __construct(
        public OAuthClientIdentifier $clientIdentifier,
        public string $redirectUri,
        public array $requestedScopes,
        public string $resource,
        public string $codeChallenge,
        public string $state,
    ) {
    }
}
