<?php

declare(strict_types=1);

namespace Source\Auth\Domain\ValueObject;

use InvalidArgumentException;

enum SocialProvider: string
{
    case GOOGLE = 'google';
    case LINE = 'line';
    case INSTAGRAM = 'instagram';

    public static function fromString(string $provider): self
    {
        return match ($provider) {
            self::GOOGLE->value => self::GOOGLE,
            self::LINE->value => self::LINE,
            self::INSTAGRAM->value => self::INSTAGRAM,
            default => throw new InvalidArgumentException('Unsupported social provider'),
        };
    }
}
