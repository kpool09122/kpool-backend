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
use Source\Monetization\Settlement\Domain\Repository\SettlementBatchRepositoryInterface;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;
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
        $monetizationAccountId = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $schedule = $this->createSchedule($monetizationAccountId);
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
            $schedule,
            $paidAmounts,
            $gatewayFeeRate,
            $platformFeeRate,
            $fixedFee,
            $periodStart,
            $periodEnd
        );

        $expectedBatch = $this->createBatch($monetizationAccountId);
        $expectedTransfer = $this->createTransfer($expectedBatch, $monetizationAccountId);
        $expectedResult = new SettlementResult($expectedBatch, $expectedTransfer);

        $settlementService = Mockery::mock(SettlementServiceInterface::class);
        $settlementService->shouldReceive('settle')
            ->once()
            ->withArgs(function (
                SettlementSchedule $sched,
                array $amounts,
                Percentage $gwFee,
                Percentage $pfFee,
                ?Money $fixed,
                DateTimeImmutable $start,
                DateTimeImmutable $end,
                DateTimeImmutable $current
            ) use ($schedule, $paidAmounts, $gatewayFeeRate, $platformFeeRate, $fixedFee, $periodStart, $periodEnd) {
                // $current is verified by type hint (DateTimeImmutable)
                unset($current);

                return $sched === $schedule
                    && $amounts === $paidAmounts
                    && $gwFee === $gatewayFeeRate
                    && $pfFee === $platformFeeRate
                    && $fixed === $fixedFee
                    && $start === $periodStart
                    && $end === $periodEnd;
            })
            ->andReturn($expectedResult);

        $settlementBatchRepository = Mockery::mock(SettlementBatchRepositoryInterface::class);
        $settlementBatchRepository->shouldReceive('save')
            ->once()
            ->with($expectedBatch);

        $transferRepository = Mockery::mock(TransferRepositoryInterface::class);
        $transferRepository->shouldReceive('save')
            ->once()
            ->with($expectedTransfer);

        $this->app->instance(SettlementServiceInterface::class, $settlementService);
        $this->app->instance(SettlementBatchRepositoryInterface::class, $settlementBatchRepository);
        $this->app->instance(TransferRepositoryInterface::class, $transferRepository);

        $useCase = $this->app->make(SettleRevenueInterface::class);

        $result = $useCase->process($input);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($expectedBatch, $result->batch());
        $this->assertSame($expectedTransfer, $result->transfer());
    }

    private function createSchedule(MonetizationAccountIdentifier $monetizationAccountIdentifier): SettlementSchedule
    {
        return new SettlementSchedule(
            new SettlementScheduleIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountIdentifier,
            new DateTimeImmutable('2024-01-10'),
            SettlementInterval::MONTHLY,
            5
        );
    }

    private function createBatch(MonetizationAccountIdentifier $monetizationAccountIdentifier): SettlementBatch
    {
        return new SettlementBatch(
            new SettlementBatchIdentifier(StrTestHelper::generateUuid()),
            $monetizationAccountIdentifier,
            Currency::JPY,
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-31'),
        );
    }

    private function createTransfer(SettlementBatch $batch, MonetizationAccountIdentifier $monetizationAccountIdentifier): Transfer
    {
        return new Transfer(
            new TransferIdentifier(StrTestHelper::generateUuid()),
            $batch->settlementBatchIdentifier(),
            $monetizationAccountIdentifier,
            new Money(7260, Currency::JPY),
        );
    }
}
