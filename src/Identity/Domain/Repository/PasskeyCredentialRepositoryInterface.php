<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Identity\Domain\ValueObject\PasskeyCredentialIdentifier;
use Source\Identity\Domain\ValueObject\WebAuthnCredentialId;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyCredentialRepositoryInterface
{
    public function save(PasskeyCredential $credential): void;

    public function findByIdentifier(PasskeyCredentialIdentifier $identifier): ?PasskeyCredential;

    public function findByCredentialId(WebAuthnCredentialId $credentialId): ?PasskeyCredential;

    /** @return PasskeyCredential[] */
    public function findByIdentityIdentifier(IdentityIdentifier $identityIdentifier): array;

    public function delete(PasskeyCredentialIdentifier $identifier): void;

    public function deleteAllExcept(
        IdentityIdentifier $identityIdentifier,
        PasskeyCredentialIdentifier $preservedIdentifier,
    ): void;
}
