<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class DraftWikiRejectionReason extends StringBaseValue
{
    public const int MAX_LENGTH = 1000;

    protected function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Draft wiki rejection reason cannot be empty.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Draft wiki rejection reason cannot exceed ' . self::MAX_LENGTH . ' characters.');
        }
    }
}
