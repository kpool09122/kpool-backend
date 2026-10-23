<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\ValueObject;

use InvalidArgumentException;

final readonly class DurationSeconds
{
    public const int MAX_VALUE = 2147483647;

    public function __construct(private int $value)
    {
        if ($value < 0 || $value > self::MAX_VALUE) {
            throw new InvalidArgumentException('DurationSeconds must be between 0 and ' . self::MAX_VALUE . '.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
