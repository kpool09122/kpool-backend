<?php

declare(strict_types=1);

namespace Source\Auth\Domain\Service;

use Source\Auth\Domain\ValueObject\OAuthState;

interface OAuthStateGeneratorInterface
{
    public function generate(): OAuthState;
}
