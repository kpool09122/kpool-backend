<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\PasskeyRecovery;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\SocialProvider;

readonly class PasskeyRecoveryOAuthSession
{
    public function __construct(
        public SocialProvider $provider,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
