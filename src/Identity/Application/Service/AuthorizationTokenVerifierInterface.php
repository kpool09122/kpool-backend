<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;

interface AuthorizationTokenVerifierInterface
{
    /** @throws InvalidAuthorizationTokenException */
    public function verify(string $bearerToken): ValidatedAuthorizationToken;
}
