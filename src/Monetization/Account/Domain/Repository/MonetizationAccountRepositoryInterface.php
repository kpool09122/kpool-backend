<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Repository;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface MonetizationAccountRepositoryInterface
{
    /**
     * @param MonetizationAccountIdentifier $identifier
     * @return MonetizationAccount|null
     */
    public function findById(MonetizationAccountIdentifier $identifier): ?MonetizationAccount;

    /**
     * @param AccountIdentifier $accountIdentifier
     * @return MonetizationAccount|null
     */
    public function findByAccountIdentifier(AccountIdentifier $accountIdentifier): ?MonetizationAccount;

    /**
     * @param MonetizationAccount $monetizationAccount
     * @return void
     */
    public function save(MonetizationAccount $monetizationAccount): void;
}
