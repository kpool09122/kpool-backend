<?php

declare(strict_types=1);

namespace Source\Account\Policy\Domain\Repository;

use Source\Account\IdentityGroup\Domain\ValueObject\AccountRole;
use Source\Account\Policy\Domain\Entity\AccountPolicy;
use Source\Account\Policy\Domain\ValueObject\AccountPolicyIdentifier;

interface AccountPolicyRepositoryInterface
{
    public function save(AccountPolicy $policy): void;

    public function attachToRole(AccountRole $role, AccountPolicyIdentifier $policyIdentifier): void;

    /**
     * @param AccountRole[] $roles
     * @return AccountPolicy[]
     */
    public function findByRoles(array $roles): array;

    /**
     * @return AccountPolicy[]
     */
    public function findAll(): array;
}
