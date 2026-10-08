<?php

declare(strict_types=1);

namespace Source\SiteManagement\Principal\Domain\Repository;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface PrincipalGroupRepositoryInterface
{
    public function findById(PrincipalGroupIdentifier $principalGroupIdentifier): ?PrincipalGroup;

    public function findDefaultByAccountIdentifier(AccountIdentifier $accountIdentifier): ?PrincipalGroup;

    public function save(PrincipalGroup $principalGroup): void;

    /** @return PrincipalGroup[] */
    public function findByPrincipalId(PrincipalIdentifier $principalIdentifier): array;
}
