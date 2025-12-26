<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class AuthCode extends StringBaseValue
{
    protected function validate(string $value): void
    {
        if (! preg_match('/\A\d{6}\z/', $value)) {
            throw new InvalidArgumentException('認証コードは6桁の数字である必要があります。');
        }
    }
}
