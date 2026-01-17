<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class StripeTransferId extends StringBaseValue
{
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(string $value): void
    {
        if (! str_starts_with($value, 'tr_') || strlen($value) < 10) {
            throw new InvalidArgumentException('Invalid Stripe Transfer ID format.');
        }
    }
}
