<?php

declare(strict_types=1);

namespace Source\Account\Invitation\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class InvitationToken extends StringBaseValue
{
    public const int TOKEN_LENGTH = 64;

    protected function validate(string $value): void
    {
        if (strlen($value) !== self::TOKEN_LENGTH) {
            throw new InvalidArgumentException(
                'InvitationToken must be ' . self::TOKEN_LENGTH . ' characters.'
            );
        }

        if (! ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'InvitationToken must be a hexadecimal string.'
            );
        }
    }
}
