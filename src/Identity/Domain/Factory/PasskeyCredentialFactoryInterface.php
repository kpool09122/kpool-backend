<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Factory;

use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyCredentialFactoryInterface
{
    /** @param string[] $transports */
    public function create(
        IdentityIdentifier $identityIdentifier,
        WebAuthnCredentialId $credentialId,
        CredentialSource $credentialSource,
        int $signCount,
        bool $backupEligible,
        bool $backupState,
        array $transports,
        PasskeyDisplayName $displayName,
    ): PasskeyCredential;
}
