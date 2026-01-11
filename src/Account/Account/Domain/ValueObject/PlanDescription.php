<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PlanDescription extends StringBaseValue
{
    public const int MAX_LENGTH = 512;

    protected function validate(string $value): void
    {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Plan description cannot be longer than ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
