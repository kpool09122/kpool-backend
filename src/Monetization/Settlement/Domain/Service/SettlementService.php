<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use DateTimeImmutable;
use DomainException;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Monetization\Settlement\Domain\Factory\TransferFactoryInterface;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

readonly class SettlementService implements SettlementServiceInterface
{
    /**
     * @param SettlementBatchFactoryInterface $batchFactory
     * @param TransferFactoryInterface $transferFactory
     * @param FeeCalculatorServiceInterface $feeCalculator
     */
    public function __construct(
        private SettlementBatchFactoryInterface $batchFactory,
        private TransferFactoryInterface        $transferFactory,
        private FeeCalculatorServiceInterface   $feeCalculator,
    ) {
    }

    /**
     * @param Money[] $paidAmounts
     */
    public function settle(
        SettlementSchedule $schedule,
        array $paidAmounts,
        Percentage $gatewayFeeRate,
        Percentage $platformFeeRate,
        ?Money $fixedFee,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
        DateTimeImmutable $currentDate
    ): SettlementResult {
        if ($paidAmounts === []) {
            throw new DomainException('No paid amounts to settle.');
        }

        $currency = $paidAmounts[0]->currency();
        $gross = $this->sumGross($currency, $paidAmounts);

        if (! $schedule->isDue($currentDate, $gross)) {
            throw new DomainException('Settlement schedule not due.');
        }

        $monetizationAccountIdentifier = $schedule->monetizationAccountIdentifier();

        $batch = $this->batchFactory->create($monetizationAccountIdentifier, $currency, $periodStart, $periodEnd);
        foreach ($paidAmounts as $amount) {
            $batch->recordRevenue($amount);
        }

        $fee = $this->feeCalculator->calculate($batch->grossAmount(), $gatewayFeeRate, $platformFeeRate, $fixedFee);
        $batch->applyFee($fee);
        $batch->markProcessing($currentDate);

        $transfer = $this->transferFactory->create(
            $batch->settlementBatchIdentifier(),
            $monetizationAccountIdentifier,
            $batch->netAmount()
        );

        $schedule->advance();

        return new SettlementResult($batch, $transfer);
    }

    /**
     * @param Money[] $paidAmounts
     */
    private function sumGross(Currency $currency, array $paidAmounts): Money
    {
        $gross = new Money(0, $currency);
        foreach ($paidAmounts as $amount) {
            if (! $amount->isSameCurrency($gross)) {
                throw new DomainException('Paid amount currency mismatch.');
            }
            $gross = $gross->add($amount);
        }

        return $gross;
    }
}
