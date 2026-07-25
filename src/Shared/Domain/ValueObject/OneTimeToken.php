<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class OneTimeToken extends StringBaseValue
{
    public const int TOKEN_LENGTH = 64;

    protected function validate(string $value): void
    {
        if (strlen($value) !== self::TOKEN_LENGTH) {
            throw new InvalidArgumentException(
                'OneTimeToken must be ' . self::TOKEN_LENGTH . ' characters.'
            );
        }

        if (! ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'OneTimeToken must be a hexadecimal string.'
            );
        }
    }
}
