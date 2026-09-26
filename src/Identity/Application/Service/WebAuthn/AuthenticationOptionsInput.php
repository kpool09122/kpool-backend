<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use Source\Identity\Domain\ValueObject\WebAuthnChallenge;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

readonly class AuthenticationOptionsInput
{
    /** @param WebAuthnCredentialId[] $allowedCredentialIds */
    public function __construct(
        public WebAuthnChallenge $challenge,
        public array $allowedCredentialIds = [],
    ) {
    }
}
