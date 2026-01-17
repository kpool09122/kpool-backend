<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Money;

interface SettleRevenueInputPort
{
    public function settlementSchedule(): SettlementSchedule;

    /**
     * @return Money[]
     */
    public function paidAmounts(): array;

    public function gatewayFeeRate(): Percentage;

    public function platformFeeRate(): Percentage;

    public function fixedFee(): ?Money;

    public function periodStart(): DateTimeImmutable;

    public function periodEnd(): DateTimeImmutable;
}
