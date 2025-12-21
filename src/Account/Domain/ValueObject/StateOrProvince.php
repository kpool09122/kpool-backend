<?php

declare(strict_types=1);

namespace Source\Account\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class StateOrProvince extends StringBaseValue
{
    public const int MAX_LENGTH = 64;

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('State or province is required.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('State or province cannot be longer than ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
