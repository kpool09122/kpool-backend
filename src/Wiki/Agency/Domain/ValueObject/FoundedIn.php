<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\DateTimeBaseValue;

class FoundedIn extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
        $now = new DateTimeImmutable();

        if ($value >= $now) {
            throw new InvalidArgumentException('Founded in must be in the past.');
        }
    }
}
