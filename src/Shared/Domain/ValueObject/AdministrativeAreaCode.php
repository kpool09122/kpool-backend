<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class AdministrativeAreaCode extends StringBaseValue
{
    public const int MAX_LENGTH = 16;

    public function __construct(string $value)
    {
        parent::__construct(trim($value));
    }

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('Administrative area code is required.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Administrative area code cannot be longer than ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
