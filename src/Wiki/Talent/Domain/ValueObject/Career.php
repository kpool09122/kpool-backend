<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Career extends StringBaseValue
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
            throw new InvalidArgumentException('Career cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
