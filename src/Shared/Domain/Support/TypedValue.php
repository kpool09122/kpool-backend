<?php

declare(strict_types=1);

namespace Source\Shared\Domain\Support;

use UnexpectedValueException;

final class TypedValue
{
    public static function string(mixed $value): string
    {
        if (! is_string($value)) {
            throw new UnexpectedValueException('Expected a string.');
        }

        return $value;
    }

    public static function nullableStringOrInt(mixed $value): string|int|null
    {
        if ($value !== null && ! is_string($value) && ! is_int($value)) {
            throw new UnexpectedValueException('Expected a string, integer or null.');
        }

        return $value;
    }

    public static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : self::string($value);
    }

    public static function int(mixed $value): int
    {
        if (! is_int($value)) {
            throw new UnexpectedValueException('Expected an integer.');
        }

        return $value;
    }

    public static function numericInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new UnexpectedValueException('Expected an integer or numeric integer string.');
    }

    public static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : self::int($value);
    }

    /** @return array<array-key, mixed> */
    public static function array(mixed $value): array
    {
        if (! is_array($value)) {
            throw new UnexpectedValueException('Expected an array.');
        }

        return $value;
    }

    /** @return array<array-key, string> */
    public static function stringArray(mixed $value): array
    {
        return array_map(self::string(...), self::array($value));
    }

    /** @return array<string, string|null> */
    public static function stringMap(mixed $value): array
    {
        $result = [];
        foreach (self::array($value) as $key => $item) {
            $result[self::string($key)] = self::nullableString($item);
        }

        return $result;
    }
}
