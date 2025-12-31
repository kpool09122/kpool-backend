<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;

class SettlementScheduleTest extends TestCase
{
    /**
     * 正常系: 清算スケジュールが正しく作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $settlementScheduleIdentifier = new SettlementScheduleIdentifier(StrTestHelper::generateUuid());
        $anchorDate = new DateTimeImmutable();
        $interval = SettlementInterval::THRESHOLD;
        $payoutDelayDays = 0;
        $threshold = new Money(100, Currency::KRW);
        $schedule = $this->createSchedule(
            $settlementScheduleIdentifier,
            $anchorDate,
            $interval,
            $payoutDelayDays,
            $threshold,
        );
        $this->assertSame($settlementScheduleIdentifier, $schedule->settlementScheduleIdentifier());
        $this->assertEquals($anchorDate, $schedule->nextClosingDate());
        $this->assertSame($interval, $schedule->interval());
        $this->assertSame($payoutDelayDays, $schedule->paymentDelayDays());
        $this->assertSame($threshold, $schedule->threshold());
    }

    /**
     * 異常系: 支払日オフセットが負の値の時、例外がスローされること.
     *
     * @return void
     */
    public function testWhenPayoutDelayDaysAreMinus(): void
    {
        $payoutDelayDays = -1;
        $this->expectException(InvalidArgumentException::class);
        $this->createSchedule(
            payoutDelayDays: $payoutDelayDays,
        );
    }

    /**
     * 異常系: 支払い間隔が閾値なのに、閾値が設定されていない場合は、例外がスローされること.
     *
     * @return void
     */
    public function testNoThresholdValueWhenIntervalIsThreshold(): void
    {
        $interval = SettlementInterval::THRESHOLD;
        $threshold = null;
        $this->expectException(InvalidArgumentException::class);
        $this->createSchedule(
            interval: $interval,
            threshold: $threshold,
        );
    }

    /**
     * 異常系: 閾値型スケジュールで支払日オフセットが指定された場合は例外となること.
     *
     * @return void
     */
    public function testThresholdIntervalRejectsPayoutDelayDays(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createSchedule(
            interval: SettlementInterval::THRESHOLD,
            payoutDelayDays: 1,
            threshold: new Money(100, Currency::JPY),
        );
    }

    /**
     * 正常系: 月次スケジュールが支払日判定と次回更新を行えること.
     *
     * @return void
     */
    public function testMonthlyScheduleAdvances(): void
    {
        $anchor = new DateTimeImmutable('2024-01-10 00:00:00');
        $schedule = $this->createSchedule(
            anchorDate:$anchor,
            interval: SettlementInterval::MONTHLY,
            payoutDelayDays: 5
        );

        $this->assertEquals(
            new DateTimeImmutable('2024-01-15 00:00:00'),
            $schedule->nextPayoutDate()
        );
        $this->assertFalse($schedule->isDue(new DateTimeImmutable('2024-01-14 12:00:00')));
        $this->assertTrue($schedule->isDue(new DateTimeImmutable('2024-01-15 00:00:00')));

        $schedule->advance();

        $this->assertEquals(
            new DateTimeImmutable('2024-02-15 00:00:00'),
            $schedule->nextPayoutDate()
        );
    }

    /**
     * 正常系: 隔週スケジュールが支払日判定と次回更新を行えること.
     *
     * @return void
     */
    public function testMonthlyScheduleAdvancesWhenBiWeekly(): void
    {
        $anchor = new DateTimeImmutable('2024-01-10 00:00:00');
        $schedule = $this->createSchedule(
            anchorDate:$anchor,
            interval: SettlementInterval::BIWEEKLY,
            payoutDelayDays: 5
        );

        $this->assertEquals(
            new DateTimeImmutable('2024-01-15 00:00:00'),
            $schedule->nextPayoutDate()
        );
        $this->assertFalse($schedule->isDue(new DateTimeImmutable('2024-01-14 12:00:00')));
        $this->assertTrue($schedule->isDue(new DateTimeImmutable('2024-01-15 00:00:00')));

        $schedule->advance();

        $this->assertEquals(
            new DateTimeImmutable('2024-01-29 00:00:00'),
            $schedule->nextPayoutDate()
        );
    }

    /**
     * 正常系: 閾値判定の時に、次回更新を行っても何もおこらないこと.
     *
     * @return void
     */
    public function testAdvanceWhenThreshold(): void
    {
        $anchorDate = new DateTimeImmutable('2024-01-10 00:00:00');
        $schedule = $this->createSchedule(
            anchorDate: $anchorDate,
            interval: SettlementInterval::THRESHOLD,
            threshold: new Money(100, Currency::KRW),
        );
        $schedule->advance();

        $this->assertEquals($anchorDate, $schedule->nextClosingDate());
    }

    /**
     * 正常系: 閾値到達型スケジュールでは残高がしきい値を超えたときのみ due になること.
     *
     * @return void
     */
    public function testThresholdScheduleChecksBalance(): void
    {
        $schedule = $this->createSchedule(
            anchorDate: new DateTimeImmutable('2024-01-01'),
            interval: SettlementInterval::THRESHOLD,
            payoutDelayDays: 0,
            threshold: new Money(5000, Currency::JPY)
        );

        $this->assertFalse($schedule->isDue(new DateTimeImmutable('2024-01-02'), new Money(4000, Currency::JPY)));
        $this->assertTrue($schedule->isDue(new DateTimeImmutable('2024-01-02'), new Money(5000, Currency::JPY)));
    }

    /**
     * 異常系: 閾値判定時に精算に回せる残高が存在しない場合は例外となること.
     *
     * @return void
     */
    public function testThresholdScheduleRejectsNoAvailableBalance(): void
    {
        $schedule = $this->createSchedule(
            anchorDate: new DateTimeImmutable('2024-01-01'),
            interval: SettlementInterval::THRESHOLD,
            payoutDelayDays: 0,
            threshold: new Money(6000, Currency::USD)
        );

        $this->expectException(DomainException::class);

        $schedule->isDue(new DateTimeImmutable('2024-01-10'), null);
    }

    /**
     * 異常系: 閾値判定時に通貨が異なる場合は例外となること.
     *
     * @return void
     */
    public function testThresholdScheduleRejectsDifferentCurrency(): void
    {
        $schedule = $this->createSchedule(
            anchorDate: new DateTimeImmutable('2024-01-01'),
            interval: SettlementInterval::THRESHOLD,
            payoutDelayDays: 0,
            threshold: new Money(5000, Currency::JPY)
        );

        $this->expectException(DomainException::class);

        $schedule->isDue(new DateTimeImmutable('2024-01-10'), new Money(6000, Currency::USD));
    }

    private function createSchedule(
        ?SettlementScheduleIdentifier $scheduleIdentifier = null,
        ?DateTimeImmutable $anchorDate = null,
        ?SettlementInterval $interval = null,
        ?int $payoutDelayDays = null,
        ?Money $threshold = null,
    ): SettlementSchedule {
        return new SettlementSchedule(
            $scheduleIdentifier ?? new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            $anchorDate ?? new DateTimeImmutable('2024-01-01'),
            $interval ?? SettlementInterval::MONTHLY,
            $payoutDelayDays ?? 0,
            $threshold ?? null,
        );
    }
}
