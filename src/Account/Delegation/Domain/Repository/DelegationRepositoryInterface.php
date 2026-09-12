<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Repository;

use Source\Account\Delegation\Domain\Entity\Delegation;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

interface DelegationRepositoryInterface
{
    public function save(Delegation $delegation): void;

    public function findById(DelegationIdentifier $identifier): ?Delegation;

    public function findOpenByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?Delegation;
}
