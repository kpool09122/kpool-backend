<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\Factory;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

interface MonetizationAccountFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier): MonetizationAccount;
}
