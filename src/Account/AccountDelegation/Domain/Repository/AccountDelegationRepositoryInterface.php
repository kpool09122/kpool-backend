<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Domain\Repository;

use Source\Account\AccountDelegation\Domain\Entity\AccountDelegation;
use Source\Account\Shared\Domain\ValueObject\AffiliationIdentifier;

interface AccountDelegationRepositoryInterface
{
    public function save(AccountDelegation $delegation): void;

    public function existsOpenByAffiliation(AffiliationIdentifier $affiliationIdentifier): bool;
}
