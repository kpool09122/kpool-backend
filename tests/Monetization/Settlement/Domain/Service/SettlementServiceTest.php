<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\Service;

use DateTimeImmutable;
use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;
use Source\Monetization\Settlement\Domain\ValueObject\TransferStatus;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    /**
     * 正常系: スケジュール到来時に売上を集計し Transfer を作成できること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSettleCreatesBatchAndTransfer(): void
    {
        $service = $this->app->make(SettlementServiceInterface::class);
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $schedule = new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new DateTimeImmutable('2024-01-10'),
            SettlementInterval::MONTHLY,
            5
        );

        $result = $service->settle(
            $schedule,
            [
                new Money(5000, Currency::JPY),
                new Money(3000, Currency::JPY),
            ],
            new Percentage(3),
            new Percentage(5),
            new Money(100, Currency::JPY),
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            new DateTimeImmutable('2024-01-20')
        );

        $batch = $result->batch();
        $transfer = $result->transfer();

        $this->assertSame(SettlementStatus::PROCESSING, $batch->status());
        $this->assertSame(8000, $batch->grossAmount()->amount());
        $this->assertSame(740, $batch->feeAmount()->amount());
        $this->assertSame(7260, $batch->netAmount()->amount());

        $this->assertSame($batch->settlementBatchIdentifier(), $transfer->settlementBatchIdentifier());
        $this->assertSame(TransferStatus::PENDING, $transfer->status());
        $this->assertSame($batch->netAmount()->amount(), $transfer->amount()->amount());

        $this->assertEquals(new DateTimeImmutable('2024-02-15'), $schedule->nextPayoutDate());
    }

    /**
     * 異常系: 清算対象がなければ、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSettleWhenNoAmount(): void
    {
        $service = $this->app->make(SettlementServiceInterface::class);
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $schedule = new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new DateTimeImmutable('2024-02-10'),
            SettlementInterval::MONTHLY,
            5
        );

        $this->expectException(DomainException::class);

        $service->settle(
            $schedule,
            [],
            new Percentage(1),
            new Percentage(1),
            null,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            new DateTimeImmutable('2024-01-12')
        );
    }

    /**
     * 異常系: 清算対象の通貨が異なる場合、例外がスローされること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSettleWhenDifferentCurrency(): void
    {
        $service = $this->app->make(SettlementServiceInterface::class);
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $schedule = new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new DateTimeImmutable('2024-02-10'),
            SettlementInterval::MONTHLY,
            5
        );

        $this->expectException(DomainException::class);

        $service->settle(
            $schedule,
            [
                new Money(5000, Currency::KRW),
                new Money(3000, Currency::JPY),
            ],
            new Percentage(1),
            new Percentage(1),
            null,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            new DateTimeImmutable('2024-01-12')
        );
    }

    /**
     * 異常系: スケジュールが到来していなければ精算できないこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testSettleBeforeDueThrows(): void
    {
        $service = $this->app->make(SettlementServiceInterface::class);
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $schedule = new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountId,
            new DateTimeImmutable('2024-01-10'),
            SettlementInterval::MONTHLY,
            5
        );

        $this->expectException(DomainException::class);

        $service->settle(
            $schedule,
            [new Money(1000, Currency::JPY)],
            new Percentage(1),
            new Percentage(1),
            null,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
            new DateTimeImmutable('2024-01-12')
        );
    }
}
