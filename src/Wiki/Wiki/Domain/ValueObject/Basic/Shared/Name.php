<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Name extends StringBaseValue
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
        if (empty($value)) {
            throw new InvalidArgumentException('Name cannot be empty');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Name cannot exceed ' . self::MAX_LENGTH . ' characters');
        }
    }
}
