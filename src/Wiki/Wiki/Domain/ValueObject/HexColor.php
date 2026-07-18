<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class HexColor extends StringBaseValue
{
    private const string HEX_PATTERN = '/^#[A-Fa-f0-9]{6}$/';

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('Hex color cannot be empty.');
        }

        if (! preg_match(self::HEX_PATTERN, $value)) {
            throw new InvalidArgumentException('Hex color must be a valid #RRGGBB color code (e.g., #FF5733).');
        }
    }
}
