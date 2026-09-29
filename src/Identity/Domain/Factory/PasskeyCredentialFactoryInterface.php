<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;

interface PasskeyCredentialFactoryInterface
{
    /** @param string[] $transports */
    public function create(
        PasskeyUserIdentifier $passkeyUserIdentifier,
        WebAuthnCredentialId $credentialId,
        CredentialSource $credentialSource,
        int $signCount,
        bool $backupEligible,
        bool $backupState,
        array $transports,
        PasskeyDisplayName $displayName,
    ): PasskeyCredential;
}
