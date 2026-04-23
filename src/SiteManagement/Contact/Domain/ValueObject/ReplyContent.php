<?php

declare(strict_types=1);

namespace Source\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ReplyContent extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('Reply content cannot be empty');
        }
    }
}
