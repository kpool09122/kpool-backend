<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueInput;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettleRevenueInputTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $account = new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUlid()),
            new UserIdentifier(StrTestHelper::generateUlid()),
            'KBank',
            '1234',
            Currency::JPY,
            true
        );
        $schedule = new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUlid()),
            new DateTimeImmutable('2024-01-10'),
            SettlementInterval::MONTHLY,
            5
        );
        $paidAmounts = [
            new Money(5000, Currency::JPY),
            new Money(3000, Currency::JPY),
        ];
        $gatewayFeeRate = new Percentage(3);
        $platformFeeRate = new Percentage(5);
        $fixedFee = new Money(100, Currency::JPY);
        $periodStart = new DateTimeImmutable('2024-01-01');
        $periodEnd = new DateTimeImmutable('2024-01-31');

        $input = new SettleRevenueInput(
            $account,
            $schedule,
            $paidAmounts,
            $gatewayFeeRate,
            $platformFeeRate,
            $fixedFee,
            $periodStart,
            $periodEnd
        );

        $this->assertSame($account, $input->settlementAccount());
        $this->assertSame($schedule, $input->settlementSchedule());
        $this->assertSame($paidAmounts, $input->paidAmounts());
        $this->assertSame($gatewayFeeRate, $input->gatewayFeeRate());
        $this->assertSame($platformFeeRate, $input->platformFeeRate());
        $this->assertSame($fixedFee, $input->fixedFee());
        $this->assertSame($periodStart, $input->periodStart());
        $this->assertSame($periodEnd, $input->periodEnd());
    }

    /**
     * 正常系: fixedFeeがnullでもインスタンスが作成できること.
     *
     * @return void
     */
    public function test__constructWithoutFixedFee(): void
    {
        $account = new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUlid()),
            new UserIdentifier(StrTestHelper::generateUlid()),
            'KBank',
            '1234',
            Currency::JPY,
            true
        );
        $schedule = new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUlid()),
            new DateTimeImmutable('2024-01-10'),
            SettlementInterval::MONTHLY,
            5
        );

        $input = new SettleRevenueInput(
            $account,
            $schedule,
            [new Money(1000, Currency::JPY)],
            new Percentage(1),
            new Percentage(1),
            null,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31')
        );

        $this->assertNull($input->fixedFee());
    }
}
