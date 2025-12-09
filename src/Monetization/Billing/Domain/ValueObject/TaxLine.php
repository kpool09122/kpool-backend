<?php

declare(strict_types=1);

namespace Source\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Money;

readonly class TaxLine
{
    public function __construct(
        private string $label,
        private Percentage $rate,
        private bool $inclusive,
    ) {
        $this->assertLabel($label);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function rate(): Percentage
    {
        return $this->rate;
    }

    public function isInclusive(): bool
    {
        return $this->inclusive;
    }

    public function taxAmountFor(Money $amount): Money
    {
        $rateValue = $this->rate->value();
        $taxAmount = $this->inclusive
            ? intdiv($amount->amount() * $rateValue, 100 + $rateValue)
            : intdiv($amount->amount() * $rateValue, 100);

        return new Money($taxAmount, $amount->currency());
    }

    private function assertLabel(string $label): void
    {
        if (trim($label) === '') {
            throw new InvalidArgumentException('Tax label must not be empty.');
        }
    }
}
