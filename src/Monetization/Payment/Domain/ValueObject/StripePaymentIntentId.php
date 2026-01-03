<?php

declare(strict_types=1);

namespace Source\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class StripePaymentIntentId extends StringBaseValue
{
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->validate($id);
    }

    protected function validate(string $value): void
    {
        if (! str_starts_with($value, 'pi_') || strlen($value) < 10) {
            throw new InvalidArgumentException('Invalid Stripe Payment Intent ID format.');
        }
    }
}
