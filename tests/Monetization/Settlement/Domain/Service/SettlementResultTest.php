<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\Service;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class SettlementResultTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $batch = new SettlementBatch(new SettlementBatchIdentifier('019c9b4c-0000-7000-8000-000000000001'), new MonetizationAccountIdentifier('019c9b4c-0000-7000-8000-000000000002'), Currency::JPY, new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-10-03'));
        $transfer = new Transfer(new TransferIdentifier('019c9b4c-0000-7000-8000-000000000003'), $batch->settlementBatchIdentifier(), $batch->monetizationAccountIdentifier(), new Money(100, Currency::JPY));

        $subject = new SettlementResult($batch, $transfer);

        $this->assertSame($batch, $subject->batch());
        $this->assertSame($transfer, $subject->transfer());
    }
}
