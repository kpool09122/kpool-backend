<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Domain\Repository;

use Source\Account\Delegation\Domain\Entity\AccountDelegation;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;

interface AccountDelegationRepositoryInterface
{
    public function save(AccountDelegation $delegation): void;

    public function findById(DelegationIdentifier $identifier): ?AccountDelegation;

    public function delete(AccountDelegation $delegation): void;

    public function findOpenByAffiliationId(AffiliationIdentifier $affiliationIdentifier): ?AccountDelegation;
}
