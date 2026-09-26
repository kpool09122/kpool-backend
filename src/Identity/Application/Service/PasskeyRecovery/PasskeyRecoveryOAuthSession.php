<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class PasskeyRecoveryOAuthSession
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public SocialProvider $provider,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
