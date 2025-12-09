<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Money;

interface FeeCalculatorServiceInterface
{
    public function calculate(
        Money $grossAmount,
        Percentage $gatewayFeeRate,
        Percentage $platformFeeRate,
        ?Money $fixedFee = null
    ): Money;
}
