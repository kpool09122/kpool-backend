<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\ValueObject;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Foundation\DateTimeBaseValue;

class ReleaseDate extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
    }
}
