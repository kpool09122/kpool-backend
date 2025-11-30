<?php

declare(strict_types=1);

namespace Source\Auth\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class OAuthCode extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException('OAuth code must not be empty.');
        }
    }
}
