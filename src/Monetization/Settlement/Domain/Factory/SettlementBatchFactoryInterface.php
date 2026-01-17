<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Shared\Domain\ValueObject\Currency;

interface SettlementBatchFactoryInterface
{
    public function create(
        MonetizationAccountIdentifier $monetizationAccountIdentifier,
        Currency $currency,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd
    ): SettlementBatch;
}
