<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\Repository;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface PrincipalGroupRepositoryInterface
{
    public function save(PrincipalGroup $principalGroup): void;

    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup;

    /**
     * @return array<PrincipalGroup>
     */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array;

    /**
     * @return array<PrincipalGroup>
     */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array;

    public function findDefaultByAccountId(AccountIdentifier $accountIdentifier): ?PrincipalGroup;

    public function delete(PrincipalGroup $principalGroup): void;
}
