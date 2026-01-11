<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PlanName extends StringBaseValue
{
    public const int MAX_LENGTH = 64;

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('PlanName cannot be empty.');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('PlanName cannot be longer than ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
