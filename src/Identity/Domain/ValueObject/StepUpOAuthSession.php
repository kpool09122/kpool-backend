<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class StepUpOAuthSession
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public SocialProvider $provider,
        public StepUpAuthenticationScope $scope,
        public DateTimeImmutable $expiresAt,
        public string $returnTo,
    ) {
    }
}
