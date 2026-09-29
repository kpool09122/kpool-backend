<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

readonly class RegistrationOptionsInput
{
    /** @param WebAuthnCredentialId[] $excludedCredentialIds */
    public function __construct(
        public WebAuthnChallenge $challenge,
        public string $userHandle,
        public string $userName,
        public string $userDisplayName,
        public array $excludedCredentialIds,
    ) {
    }
}
