<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\DateTimeBaseValue;
use DateTimeImmutable;

class PublishedDate extends DateTimeBaseValue
{
    protected function validate(
        DateTimeImmutable $value,
    ): void {
    }
}
