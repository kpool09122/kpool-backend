<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\AccountTypeChangeRequest;
use Source\Account\Account\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountTypeChangeRequestFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, AccountType $currentAccountType, AccountType $requestedAccountType): AccountTypeChangeRequest;
}
