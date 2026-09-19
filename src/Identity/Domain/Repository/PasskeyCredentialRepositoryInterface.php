<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\PasskeyCredential;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface PasskeyCredentialRepositoryInterface
{
    public function findByCredentialId(string $credentialId): ?PasskeyCredential;

    public function findByIdentifier(string $identifier): ?PasskeyCredential;

    /** @return PasskeyCredential[] */
    public function findByIdentity(IdentityIdentifier $identityIdentifier): array;

    /** @return PasskeyCredential[] */
    public function findByIdentityForUpdate(IdentityIdentifier $identityIdentifier): array;

    public function save(PasskeyCredential $credential): void;

    public function delete(PasskeyCredential $credential): void;
}
