<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Talent;

use InvalidArgumentException;

final readonly class Height
{
    public function __construct(
        private int $centimeters,
    ) {
        if ($centimeters <= 0) {
            throw new InvalidArgumentException('Height must be positive.');
        }
    }

    public function centimeters(): int
    {
        return $this->centimeters;
    }
}
