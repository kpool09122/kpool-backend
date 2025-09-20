<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\ValueObject;

use DateTimeImmutable;
use Source\Shared\Domain\ValueObject\Foundation\DateTimeBaseValue;

class PublishedDate extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
    }
}
