<?php

declare(strict_types=1);

namespace Source\Identity\Domain\Repository;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\ValueObject\SocialProvider;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Source\Shared\Domain\ValueObject\Email;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentityRepositoryInterface
{
    public function findByEmail(Email $email): ?Identity;

    public function findBySocialConnection(SocialProvider $provider, string $providerUserId): ?Identity;

    public function save(Identity $identity): void;

    public function findById(IdentityIdentifier $identifier): ?Identity;

    public function findByDelegation(DelegationIdentifier $delegationIdentifier): ?Identity;

    /**
     * @return Identity[]
     */
    public function findDelegatedIdentities(IdentityIdentifier $originalIdentityIdentifier): array;

    public function deleteByDelegation(DelegationIdentifier $delegationIdentifier): void;
}
