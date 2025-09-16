<?php

namespace Businesses\Wiki\Agency\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\DateTimeBaseValue;
use DateTimeImmutable;
use InvalidArgumentException;

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
