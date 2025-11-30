<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

use InvalidArgumentException;

readonly class Money
{
    public function __construct(
        private int      $amount,
        private Currency $currency,
    ) {
        $this->validate($amount);
    }

    private function validate(int $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount must be greater than 0');
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
}
