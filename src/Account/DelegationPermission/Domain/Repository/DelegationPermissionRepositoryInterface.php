<?php

declare(strict_types=1);

namespace Source\Account\DelegationPermission\Domain\Repository;

use Source\Account\DelegationPermission\Domain\Entity\DelegationPermission;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Account\Shared\Domain\ValueObject\IdentityGroupIdentifier;
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
