<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PasskeyDisplayName extends StringBaseValue
{
    public const int MAX_LENGTH = 64;

    protected function validate(string $value): void
    {
        if (mb_strlen($value) < 1 || mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Passkey display name must be between 1 and 64 characters.');
        }
    }
}
