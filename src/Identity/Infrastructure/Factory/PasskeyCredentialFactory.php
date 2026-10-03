<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Factory;

use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\Factory\PasskeyCredentialFactoryInterface;
use Source\Identity\Domain\ValueObject\CredentialSource;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\PasskeyDisplayName;
use Source\Identity\Domain\ValueObject\PasskeyUserIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;

readonly class PasskeyCredentialFactory implements PasskeyCredentialFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function create(
        PasskeyUserIdentifier $passkeyUserIdentifier,
        WebAuthnCredentialId $credentialId,
        CredentialSource $credentialSource,
        int $signCount,
        bool $backupEligible,
        bool $backupState,
        array $transports,
        PasskeyDisplayName $displayName,
    ): PasskeyCredential {
        return new PasskeyCredential(
            new PasskeyCredentialIdentifier($this->uuidGenerator->generate()),
            $passkeyUserIdentifier,
            $credentialId,
            $credentialSource,
            $signCount,
            $backupEligible,
            $backupState,
            $transports,
            $displayName,
            null,
        );
    }
}
