<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use DateTimeInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueOutput;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettleRevenueOutputTest extends TestCase
{
    /**
     * 正常系: SettlementResultが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutResult(): void
    {
        $output = new SettleRevenueOutput();
        $this->assertSame([], $output->toArray());
    }

    /**
     * 正常系: SettlementResultがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithResult(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $periodStart = new DateTimeImmutable('2024-01-01T00:00:00+00:00');
        $periodEnd = new DateTimeImmutable('2024-01-31T23:59:59+00:00');

        $batchIdentifier = new SettlementBatchIdentifier(StrTestHelper::generateUuid());
        $batch = new SettlementBatch(
            $batchIdentifier,
            $monetizationAccountIdentifier,
            Currency::JPY,
            $periodStart,
            $periodEnd,
        );

        $transfer = new Transfer(
            new TransferIdentifier(StrTestHelper::generateUuid()),
            $batchIdentifier,
            $monetizationAccountIdentifier,
            new Money(8000, Currency::JPY),
        );

        $result = new SettlementResult($batch, $transfer);

        $output = new SettleRevenueOutput();
        $output->setResult($result);

        $data = $output->toArray();

        $this->assertSame((string) $batch->settlementBatchIdentifier(), $data['settlementBatchIdentifier']);
        $this->assertSame((string) $monetizationAccountIdentifier, $data['monetizationAccountIdentifier']);
        $this->assertSame('JPY', $data['currency']);
        $this->assertSame(0, $data['grossAmount']);
        $this->assertSame(0, $data['feeAmount']);
        $this->assertSame(0, $data['netAmount']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame($periodStart->format(DateTimeInterface::ATOM), $data['periodStart']);
        $this->assertSame($periodEnd->format(DateTimeInterface::ATOM), $data['periodEnd']);
        $this->assertSame((string) $transfer->transferIdentifier(), $data['transferIdentifier']);
        $this->assertSame('pending', $data['transferStatus']);
    }
}
