<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class StepUpAuthentication
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public StepUpAuthenticationMethod $method,
        public DateTimeImmutable $verifiedAt,
        public StepUpAuthenticationScope $scope,
        public DateTimeImmutable $expiresAt,
    ) {
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $this->expiresAt <= $now;
    }
}
