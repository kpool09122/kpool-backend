<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

readonly class AuthorizationCodeParameters
{
    /** @param list<string> $requestedScopes */
    public function __construct(
        public string $clientIdentifier,
        public string $redirectUri,
        public array $requestedScopes,
        public string $resource,
        public string $codeChallenge,
        public string $state,
    ) {
    }
}
