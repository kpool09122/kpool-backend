<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\ValueObject;

use InvalidArgumentException;

class WarningCount
{
    public const int WARNING_THRESHOLD = 2;
    public const int DEMOTION_THRESHOLD = 3;

    public function __construct(private readonly int $value)
    {
        $this->validate();
    }

    public function value(): int
    {
        return $this->value;
    }

    public function increment(): self
    {
        return new self($this->value + 1);
    }

    public function reset(): self
    {
        return new self(0);
    }

    public function isReachedWarningThreshold(): bool
    {
        return $this->value === self::WARNING_THRESHOLD;
    }

    public function isExceedDemotionThreshold(): bool
    {
        return $this->value >= self::DEMOTION_THRESHOLD;
    }

    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Warning count must be greater than 0');
        }
    }
}
