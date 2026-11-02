<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Domain\Repository;

use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface PrincipalGroupRepositoryInterface
{
    /** @return PrincipalGroup[] */
    public function findByAccountId(AccountIdentifier $accountIdentifier): array;

    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup;

    public function findDefaultByAccountIdentifier(AccountIdentifier $accountIdentifier): ?PrincipalGroup;

    public function findByAccountIdAndName(AccountIdentifier $accountIdentifier, string $name): ?PrincipalGroup;

    public function delete(PrincipalGroup $principalGroup): void;

    public function save(PrincipalGroup $principalGroup): void;

    /** @return PrincipalGroup[] */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array;
}
