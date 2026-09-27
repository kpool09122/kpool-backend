<?php

declare(strict_types=1);

namespace Application\Http\Action\Support;

use InvalidArgumentException;
use Stringable;

/** Runtime narrowing of values entering HTTP actions. */
final class RequestValue
{
    public static function string(mixed $value): string
    {
        if ($value === null || is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        throw new InvalidArgumentException('Expected a string-compatible value.');
    }

    public static function integer(mixed $value): int
    {
        if ($value === null || is_scalar($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Expected an integer-compatible value.');
    }

    /** @return array<array-key, mixed> */
    public static function array(mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException('Expected an array.');
        }

        return $value;
    }

    /** @return array<string, mixed> */
    public static function object(mixed $value): array
    {
        $result = [];
        foreach (self::array($value) as $key => $item) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Expected an object with string keys.');
            }
            $result[$key] = $item;
        }

        return $result;
    }

    /** @return list<mixed> */
    public static function values(mixed $value): array
    {
        return array_values(self::array($value));
    }

    /** @return list<string> */
    public static function strings(mixed $value): array
    {
        return array_map(self::string(...), self::values($value));
    }

    /** @return list<array<string, mixed>> */
    public static function objects(mixed $value): array
    {
        return array_map(self::object(...), self::values($value));
    }
}
