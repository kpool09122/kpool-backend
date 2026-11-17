<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Identity\Domain\Entity\AuthorizationConnection;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class ValidatedAuthorizationToken
{
    /** @param list<AuthorizationScope> $scopes */
    public function __construct(
        public string $tokenIdentifier,
        public AuthorizationConnection $connection,
        public array $scopes,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
