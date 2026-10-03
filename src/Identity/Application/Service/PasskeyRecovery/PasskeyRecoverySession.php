<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PasskeyRecoverySession
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public string $method,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
