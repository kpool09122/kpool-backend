<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Group;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Foundation\DateTimeBaseValue;

class DisbandDate extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
    }
}
