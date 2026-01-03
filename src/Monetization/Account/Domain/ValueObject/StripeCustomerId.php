<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class StripeCustomerId extends StringBaseValue
{
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(string $value): void
    {
        // Stripe Customer ID は "cus_" で始まり、十分な長さが必要
        if (! str_starts_with($value, 'cus_') || strlen($value) < 10) {
            throw new InvalidArgumentException('Invalid Stripe Customer ID format.');
        }
    }
}
