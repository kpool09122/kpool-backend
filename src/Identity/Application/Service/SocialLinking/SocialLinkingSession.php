<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\SocialLinking;

use DateTimeImmutable;
use Source\Identity\Domain\ValueObject\SocialConnection;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class SocialLinkingSession
{
    public function __construct(
        public IdentityIdentifier $identityIdentifier,
        public Email $email,
        public SocialConnection $connection,
        public string $returnTo,
        public DateTimeImmutable $expiresAt,
    ) {
    }
}
