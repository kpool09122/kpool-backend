<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PaymentMethodId extends StringBaseValue
{
    private const int MAX_LENGTH = 10;

    protected function validate(string $value): void
    {
        if (! str_starts_with($value, 'pm_') || strlen($value) < self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid Payment Method ID format.');
        }
    }
}
