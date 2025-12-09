<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use DomainException;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Money;

readonly class FeeCalculatorService implements FeeCalculatorServiceInterface
{
    public function calculate(
        Money $grossAmount,
        Percentage $gatewayFeeRate,
        Percentage $platformFeeRate,
        ?Money $fixedFee = null
    ): Money {
        $fixedFeeAmount = $fixedFee?->amount() ?? 0;
        if ($fixedFee !== null && ! $fixedFee->isSameCurrency($grossAmount)) {
            throw new DomainException('Fixed fee currency must match gross amount.');
        }

        $gatewayFeeAmount = intdiv($grossAmount->amount() * $gatewayFeeRate->value(), 100);
        $platformFeeAmount = intdiv($grossAmount->amount() * $platformFeeRate->value(), 100);
        $totalFeeAmount = $gatewayFeeAmount + $platformFeeAmount + $fixedFeeAmount;

        if ($totalFeeAmount > $grossAmount->amount()) {
            throw new DomainException('Total fee cannot exceed gross amount.');
        }

        return new Money($totalFeeAmount, $grossAmount->currency());
    }
}
