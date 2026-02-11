<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Agency;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class CEO extends StringBaseValue
{
    public const int MAX_LENGTH = 64;

    public function __construct(
        protected readonly string $name,
    ) {
        parent::__construct($name);
        $this->validate($name);
    }

    protected function validate(
        string $value,
    ): void {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('CEO cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
