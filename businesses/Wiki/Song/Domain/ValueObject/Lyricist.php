<?php

namespace Businesses\Wiki\Song\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\StringBaseValue;
use InvalidArgumentException;

class Lyricist extends StringBaseValue
{
    public const int MAX_LENGTH = 32;

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
            throw new InvalidArgumentException('Lyricist cannot exceed  ' . self::MAX_LENGTH . '  characters');
        }
    }
}
