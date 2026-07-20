<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\Repository;

use Source\Account\Principal\Domain\Entity\Principal;
use Source\Account\Principal\Domain\Entity\PrincipalGroup;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PrincipalGroupRepositoryInterface
{
    public function save(PrincipalGroup $principalGroup): void;

    public function findById(PrincipalGroupIdentifier $identifier): ?PrincipalGroup;

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array;

    /**
     * @return array<PrincipalGroup>
     */
    public function findByPrincipal(Principal $principal): array;

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountIdAndPrincipal(
        AccountIdentifier $accountIdentifier,
        Principal $principal
    ): array;

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?PrincipalGroup;

    public function findByAccountIdAndRole(
        AccountIdentifier $accountIdentifier,
        AccountRole $role
    ): ?PrincipalGroup;

    public function delete(PrincipalGroup $principalGroup): void;
}
