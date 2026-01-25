<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class Slug extends StringBaseValue
{
    public const int MIN_LENGTH = 3;
    public const int MAX_LENGTH = 80;

    private const array RESERVED_WORDS = [
        'admin',
        'api',
        'www',
        'null',
        'undefined',
        'new',
        'edit',
        'delete',
        'create',
        'update',
        'settings',
        'search',
    ];

    protected function validate(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException('Slug cannot be empty');
        }

        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw new InvalidArgumentException('Slug must be at least ' . self::MIN_LENGTH . ' characters');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Slug cannot exceed ' . self::MAX_LENGTH . ' characters');
        }

        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value)) {
            throw new InvalidArgumentException('Slug must contain only lowercase letters, numbers, and hyphens. Cannot start or end with hyphen, and cannot have consecutive hyphens.');
        }

        if (in_array($value, self::RESERVED_WORDS, true)) {
            throw new InvalidArgumentException('Slug cannot be a reserved word');
        }
    }
}
