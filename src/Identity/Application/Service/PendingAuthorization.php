<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PendingAuthorization
{
    public function __construct(
        public string $identifier,
        public AuthorizationCodeParameters $parameters,
        public DateTimeImmutable $expiresAt,
        // Sessions created before login was required may still contain an unbound request.
        public ?IdentityIdentifier $identityIdentifier,
        public bool $completed = false,
    ) {
    }
}
