<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\ArchivedAccount;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface ArchivedAccountFactoryInterface
{
    public function create(
        AccountIdentifier $accountIdentifier,
        AccountCategory $accountCategory,
        AccountType $accountType,
    ): ArchivedAccount;
}
