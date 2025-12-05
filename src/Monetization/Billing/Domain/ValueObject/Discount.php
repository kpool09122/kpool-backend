<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

use Source\Shared\Domain\ValueObject\Money;

readonly class Discount
{
    public function __construct(
        private Percentage $percentage,
        private ?string $code = null,
    ) {
    }

    public function percentage(): Percentage
    {
        return $this->percentage;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function apply(Money $money): Money
    {
        return $money->subtract($this->amountFor($money));
    }

    public function amountFor(Money $money): Money
    {
        $amount = intdiv($money->amount() * $this->percentage->value(), 100);

        return new Money($amount, $money->currency());
    }
}
