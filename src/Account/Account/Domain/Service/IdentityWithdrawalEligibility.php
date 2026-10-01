<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\Service;

use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Source\Shared\Domain\ValueObject\AccountCategory;

readonly class IdentityWithdrawalEligibility
{
    /** @throws IdentityWithdrawalNotAllowedException */
    public function assertAllowed(AccountCategory $category, ?AccountType $type, bool $isSystemOwner): void
    {
        if ($category !== AccountCategory::GENERAL || $type === null
            || ($type === AccountType::CORPORATION && $isSystemOwner)) {
            throw new IdentityWithdrawalNotAllowedException('This account membership does not permit self-service withdrawal.');
        }
    }
}
