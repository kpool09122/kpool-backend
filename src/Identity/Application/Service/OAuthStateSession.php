<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\OAuthState;

readonly class OAuthStateSession
{
    public function __construct(
        public OAuthState $state,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
