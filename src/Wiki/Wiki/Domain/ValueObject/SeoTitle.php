<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class SeoTitle extends StringBaseValue
{
    public const int MAX_LENGTH = 40;

    protected function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('SEO title cannot be empty.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('SEO title cannot exceed ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
