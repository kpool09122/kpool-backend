<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Repository;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface MonetizationAccountRepositoryInterface
{
    public function findById(MonetizationAccountIdentifier $identifier): ?MonetizationAccount;

    public function findByAccountIdentifier(AccountIdentifier $accountIdentifier): ?MonetizationAccount;

    public function save(MonetizationAccount $monetizationAccount): void;
}
