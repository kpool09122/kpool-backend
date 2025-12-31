<?php

declare(strict_types=1);

namespace Source\Shared\Application\Service\Uuid;

class UuidValidator
{
    public static function isValid(string $value): bool
    {
        // UUIDv7形式: xxxxxxxx-xxxx-7xxx-yxxx-xxxxxxxxxxxx (36文字)
        // yは8, 9, a, b のいずれか (variant bits)
        if (mb_strlen($value) !== 36) {
            return false;
        }

        // UUIDv7の正規表現パターン
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $value) === 1;
    }
}
