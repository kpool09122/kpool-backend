<?php

declare(strict_types=1);

namespace Source\Identity\Application\Service\WebAuthn;

use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

readonly class VerifiedPasskeyCredential
{
    /** @param string[] $transports */
    public function __construct(
        public WebAuthnCredentialId $credentialId,
        public CredentialSource $credentialSource,
        public int $signCount,
        public bool $backupEligible,
        public bool $backupState,
        public array $transports,
    ) {
    }
}
