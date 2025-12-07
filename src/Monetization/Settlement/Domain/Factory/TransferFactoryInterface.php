<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Factory;

use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Shared\Domain\ValueObject\Money;

interface TransferFactoryInterface
{
    public function create(
        SettlementBatchIdentifier $settlementBatchIdentifier,
        SettlementAccount $settlementAccount,
        Money $amount
    ): Transfer;
}
