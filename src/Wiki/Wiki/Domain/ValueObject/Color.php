<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Color extends StringBaseValue
{
    private const string HEX_PATTERN = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    protected function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Color cannot be empty.');
        }

        if (! preg_match(self::HEX_PATTERN, $value)) {
            throw new InvalidArgumentException(
                'Color must be a valid HEX color code (e.g., #FF5733 or #F00).'
            );
        }
    }
}
