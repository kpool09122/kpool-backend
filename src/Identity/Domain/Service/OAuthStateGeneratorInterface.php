<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Random\RandomException;
use Source\Identity\Domain\ValueObject\OAuthState;

interface OAuthStateGeneratorInterface
{
    /**
     * @return OAuthState
     * @throws RandomException
     */
    public function generate(): OAuthState;
}
