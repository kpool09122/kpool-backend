<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use DateTimeImmutable;
use DomainException;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\Factory\SettlementBatchFactoryInterface;
use Source\Monetization\Settlement\Domain\Factory\TransferFactoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Shared\ValueObject\Percentage;
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
        SettlementAccount $account,
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

        $gross = $this->sumGross($account, $paidAmounts);

        if (! $schedule->isDue($currentDate, $gross)) {
            throw new DomainException('Settlement schedule not due.');
        }

        $batch = $this->batchFactory->create($account, $periodStart, $periodEnd);
        foreach ($paidAmounts as $amount) {
            $batch->recordRevenue($amount);
        }

        $fee = $this->feeCalculator->calculate($batch->grossAmount(), $gatewayFeeRate, $platformFeeRate, $fixedFee);
        $batch->applyFee($fee);
        $batch->markProcessing($currentDate);

        $transfer = $this->transferFactory->create(
            $batch->settlementBatchIdentifier(),
            $account,
            $batch->netAmount()
        );
        $batch->attachTransfer($transfer);

        $schedule->advance();

        return new SettlementResult($batch, $transfer);
    }

    /**
     * @param Money[] $paidAmounts
     */
    private function sumGross(SettlementAccount $account, array $paidAmounts): Money
    {
        $gross = new Money(0, $account->currency());
        foreach ($paidAmounts as $amount) {
            if (! $amount->isSameCurrency($gross)) {
                throw new DomainException('Paid amount currency must match settlement account.');
            }
            $gross = $gross->add($amount);
        }

        return $gross;
    }
}
