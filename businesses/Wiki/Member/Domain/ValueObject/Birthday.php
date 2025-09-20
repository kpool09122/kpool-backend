<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\DateTimeBaseValue;
use DateTimeImmutable;
use InvalidArgumentException;

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
