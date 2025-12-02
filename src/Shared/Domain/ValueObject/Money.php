<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use DomainException;
use InvalidArgumentException;

readonly class Money
{
    public function __construct(
        private int $amount,
        private Currency $currency,
    ) {
        $this->validate($amount);
    }

    private function validate(int $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be zero or greater.');
        }
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function isSameCurrency(Money $other): bool
    {
        return $this->currency === $other->currency();
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other);

        return new Money($this->amount + $other->amount(), $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other);
        $result = $this->amount - $other->amount();
        if ($result < 0) {
            throw new DomainException('Resulting amount cannot be negative.');
        }

        return new Money($result, $this->currency);
    }

    private function assertSameCurrency(Money $other): void
    {
        if (! $this->isSameCurrency($other)) {
            throw new DomainException('Currency mismatch.');
        }
    }
}
