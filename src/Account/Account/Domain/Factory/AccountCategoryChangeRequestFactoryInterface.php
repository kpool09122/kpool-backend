<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Factory;

use Source\Account\Account\Domain\Entity\AccountCategoryChangeRequest;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface AccountCategoryChangeRequestFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, AccountCategory $currentAccountCategory, AccountCategory $requestedAccountCategory): AccountCategoryChangeRequest;
}
