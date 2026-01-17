<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Money;

interface SettlementServiceInterface
{
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
    ): SettlementResult;
}
