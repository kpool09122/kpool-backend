<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Service;

use Source\Identity\Domain\ValueObject\OAuthState;

interface OAuthStateGeneratorInterface
{
    public function generate(): OAuthState;
}
