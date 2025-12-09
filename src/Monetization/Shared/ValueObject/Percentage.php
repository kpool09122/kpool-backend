<?php

declare(strict_types=1);

namespace Source\Monetization\Shared\ValueObject;

use InvalidArgumentException;

readonly class Percentage
{
    public function __construct(
        private int $value,
    ) {
        $this->assertRange($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    private function assertRange(int $value): void
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100.');
        }
    }
}
