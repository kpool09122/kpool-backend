<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccount;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Money;

readonly class SettleRevenueInput implements SettleRevenueInputPort
{
    /**
     * @param Money[] $paidAmounts
     */
    public function __construct(
        private SettlementAccount $settlementAccount,
        private SettlementSchedule $settlementSchedule,
        private array $paidAmounts,
        private Percentage $gatewayFeeRate,
        private Percentage $platformFeeRate,
        private ?Money $fixedFee,
        private DateTimeImmutable $periodStart,
        private DateTimeImmutable $periodEnd,
    ) {
    }

    public function settlementAccount(): SettlementAccount
    {
        return $this->settlementAccount;
    }

    public function settlementSchedule(): SettlementSchedule
    {
        return $this->settlementSchedule;
    }

    /**
     * @return Money[]
     */
    public function paidAmounts(): array
    {
        return $this->paidAmounts;
    }

    public function gatewayFeeRate(): Percentage
    {
        return $this->gatewayFeeRate;
    }

    public function platformFeeRate(): Percentage
    {
        return $this->platformFeeRate;
    }

    public function fixedFee(): ?Money
    {
        return $this->fixedFee;
    }

    public function periodStart(): DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function periodEnd(): DateTimeImmutable
    {
        return $this->periodEnd;
    }
}
