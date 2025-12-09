<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Factory;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;

interface SettlementBatchFactoryInterface
{
    public function create(
        SettlementAccount $account,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd
    ): SettlementBatch;
}
