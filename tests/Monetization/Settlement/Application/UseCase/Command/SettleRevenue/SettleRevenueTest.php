<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Mockery;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueInput;
use Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue\SettleRevenueInterface;
use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class SettleRevenueTest extends TestCase
{
    /**
     * 正常系: 売上を清算してSettlementResultを返すこと.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testProcessSettlesRevenue(): void
    {
        $account = $this->createAccount();
        $schedule = $this->createSchedule();
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

        $expectedBatch = $this->createBatch($account);
        $expectedTransfer = $this->createTransfer($expectedBatch, $account);
        $expectedResult = new SettlementResult($expectedBatch, $expectedTransfer);

        $settlementService = Mockery::mock(SettlementServiceInterface::class);
        $settlementService->shouldReceive('settle')
            ->once()
            ->withArgs(function (
                SettlementAccount $acc,
                SettlementSchedule $sched,
                array $amounts,
                Percentage $gwFee,
                Percentage $pfFee,
                ?Money $fixed,
                DateTimeImmutable $start,
                DateTimeImmutable $end,
                DateTimeImmutable $current
            ) use ($account, $schedule, $paidAmounts, $gatewayFeeRate, $platformFeeRate, $fixedFee, $periodStart, $periodEnd) {
                // $current is verified by type hint (DateTimeImmutable)
                unset($current);

                return $acc === $account
                    && $sched === $schedule
                    && $amounts === $paidAmounts
                    && $gwFee === $gatewayFeeRate
                    && $pfFee === $platformFeeRate
                    && $fixed === $fixedFee
                    && $start === $periodStart
                    && $end === $periodEnd;
            })
            ->andReturn($expectedResult);

        $this->app->instance(SettlementServiceInterface::class, $settlementService);

        $useCase = $this->app->make(SettleRevenueInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($expectedBatch, $result->batch());
        $this->assertSame($expectedTransfer, $result->transfer());
    }

    private function createAccount(): SettlementAccount
    {
        return new SettlementAccount(
            new SettlementAccountIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            'KBank',
            '1234',
            Currency::JPY,
            true
        );
    }

    private function createSchedule(): SettlementSchedule
    {
        return new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            new DateTimeImmutable('2024-01-10'),
            SettlementInterval::MONTHLY,
            5
        );
    }

    private function createBatch(SettlementAccount $account): SettlementBatch
    {
        return new SettlementBatch(
            new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $account,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
        );
    }

    private function createTransfer(SettlementBatch $batch, SettlementAccount $account): Transfer
    {
        return new Transfer(
            new TransferIdentifier(StrTestHelper::generateUuid()),
            $batch->settlementBatchIdentifier(),
            $account,
            new Money(7260, Currency::JPY),
        );
    }
}
