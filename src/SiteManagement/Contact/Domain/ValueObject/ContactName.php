<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ContactName extends StringBaseValue
{
    public const int MAX_LENGTH = 32;

    protected function validate(
        string $value,
    ): void {
        if ($value === '') {
            throw new InvalidArgumentException('Contact name cannot be empty');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Contact name cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
