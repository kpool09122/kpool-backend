<?php

declare(strict_types=1);

namespace Source\Account\Domain\Repository;

use Source\Account\Domain\Entity\IdentityGroup;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface IdentityGroupRepositoryInterface
{
    public function save(IdentityGroup $identityGroup): void;

    public function findById(IdentityGroupIdentifier $identifier): ?IdentityGroup;

    /**
     * @return array<IdentityGroup>
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array;

    /**
     * @return array<IdentityGroup>
     */
    public function findByIdentityId(IdentityIdentifier $identityIdentifier): array;

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?IdentityGroup;

    public function delete(IdentityGroup $identityGroup): void;
}
