<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\StringBaseValue;
use InvalidArgumentException;

class Overview extends StringBaseValue
{
    public const int MAX_LENGTH = 512;

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
            throw new InvalidArgumentException('Overview cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
