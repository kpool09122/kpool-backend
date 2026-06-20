<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class MetaDescription extends StringBaseValue
{
    public const int MAX_LENGTH = 140;

    protected function validate(string $value): void
    {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Meta description cannot exceed ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
