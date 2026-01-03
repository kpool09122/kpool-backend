<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class StripeConnectedAccountId extends StringBaseValue
{
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(string $value): void
    {
        // Stripe Connected Account ID は "acct_" で始まり、十分な長さが必要
        if (! str_starts_with($value, 'acct_') || strlen($value) < 10) {
            throw new InvalidArgumentException('Invalid Stripe Connected Account ID format.');
        }
    }
}
