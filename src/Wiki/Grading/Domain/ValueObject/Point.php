<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Domain\ValueObject;

use InvalidArgumentException;

readonly class Point
{
    public const int NEW_EDITOR = 10;
    public const int UPDATE_EDITOR = 5;
    public const int NEW_APPROVER = 2;
    public const int UPDATE_APPROVER = 1;
    public const int NEW_MERGER = 3;
    public const int UPDATE_MERGER = 2;
    public const int COOLDOWN_DAYS = 7;

    public const int PROMOTION_THRESHOLD = 50;
    public const float TOP_PERCENTAGE = 0.1;
    public const int MINIMUM_PROMOTED_COUNT = 10;
    public const int EVALUATION_MONTHS = 3;

    public function __construct(private int $value)
    {
        $this->validate();
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isExceedPromotionThreshold(): bool
    {
        return $this->value >= self::PROMOTION_THRESHOLD;
    }

    public function add(Point $points): self
    {
        return new self($this->value + $points->value());
    }

    public function isGreaterThenZero(): bool
    {
        return $this->value > 0;
    }

    private function validate(): void
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Point cannot be negative');
        }
    }
}
