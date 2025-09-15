<?php

declare(strict_types=1);

namespace Businesses\Shared\Service\Ulid;

class UlidValidator
{
    public static function isValid(string $value): bool
    {
        if (mb_strlen($value) !== 26) {
            return false;
        }
        if (strspn($value, '0123456789ABCDEFGHJKMNPQRSTVWXYZabcdefghjkmnpqrstvwxyz') !== 26) {
            return false;
        }

        return $value[0] <= '7';
    }
}
