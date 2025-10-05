<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\DateTimeBaseValue;

class Birthday extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
        $now = new DateTimeImmutable();

        if ($value >= $now) {
            throw new InvalidArgumentException('Birthday must be in the past.');
        }
    }
}
