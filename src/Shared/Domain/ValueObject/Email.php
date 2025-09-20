<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Email extends StringBaseValue
{
    /**
     * @param string $value
     * @return void
     */
    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('Email is required.');
        }

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email is invalid.');
        }
    }
}
