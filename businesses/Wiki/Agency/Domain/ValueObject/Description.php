<?php

namespace Businesses\Wiki\Agency\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\StringBaseValue;
use InvalidArgumentException;

class Description extends StringBaseValue
{
    public const int MAX_LENGTH = 2048;

    public function __construct(
        protected readonly string $text,
    ) {
        parent::__construct($text);
        $this->validate($text);
    }

    protected function validate(
        string $value,
    ): void {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Description cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
