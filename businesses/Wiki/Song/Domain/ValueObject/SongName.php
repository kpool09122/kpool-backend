<?php

declare(strict_types=1);

namespace Businesses\Wiki\Song\Domain\ValueObject;

use Businesses\Shared\ValueObject\Foundation\StringBaseValue;
use InvalidArgumentException;

class SongName extends StringBaseValue
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
            throw new InvalidArgumentException('Song name cannot be empty');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Song name cannot exceed  ' . self::MAX_LENGTH . '  characters');
        }
    }
}
