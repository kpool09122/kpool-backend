<?php

namespace Businesses\Wiki\Song\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\DateTimeBaseValue;
use DateTimeImmutable;

class ReleaseDate extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
    }
}
