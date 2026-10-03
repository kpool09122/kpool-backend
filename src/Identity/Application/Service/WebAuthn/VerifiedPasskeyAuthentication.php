<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use Source\Identity\Domain\ValueObject\CredentialSource;

readonly class VerifiedPasskeyAuthentication
{
    public function __construct(
        public CredentialSource $credentialSource,
        public int $signCount,
        public bool $backupEligible,
        public bool $backupState,
    ) {
    }
}
