<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class HexColor extends StringBaseValue
{
    private const string HEX_PATTERN = '/\A#[0-9a-f]{6}\z/';

    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('Hex color cannot be empty.');
        }

        if (! preg_match(self::HEX_PATTERN, $this->value)) {
            throw new InvalidArgumentException('Hex color must be a lowercase #rrggbb color code (e.g., #ff5733).');
        }
    }
}
