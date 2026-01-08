<?php

declare(strict_types=1);

namespace Source\Account\Domain\Repository;

use Source\Account\Domain\Entity\DelegationPermission;
use Source\Account\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Domain\ValueObject\IdentityGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface DelegationPermissionRepositoryInterface
{
    public function save(DelegationPermission $delegationPermission): void;

    public function findById(DelegationPermissionIdentifier $identifier): ?DelegationPermission;

    public function findByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?DelegationPermission;

    /**
     * @param array<IdentityGroupIdentifier> $identityGroupIdentifiers
     */
    public function existsForAnyIdentityGroup(array $identityGroupIdentifiers, AccountIdentifier $targetAccountIdentifier): bool;

    public function delete(DelegationPermission $delegationPermission): void;
}
