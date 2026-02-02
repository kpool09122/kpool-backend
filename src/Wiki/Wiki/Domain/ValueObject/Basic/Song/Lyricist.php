<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Song;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Lyricist extends StringBaseValue
{
    public const int MAX_LENGTH = 64;

    protected function validate(
        string $value,
    ): void {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Lyricist cannot exceed  ' . self::MAX_LENGTH . '  characters');
        }
    }
}
