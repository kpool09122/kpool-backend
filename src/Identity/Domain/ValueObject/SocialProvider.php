<?php

declare(strict_types=1);

namespace Source\Identity\Domain\ValueObject;

use InvalidArgumentException;

enum SocialProvider: string
{
    case GOOGLE = 'google';
    case LINE = 'line';
    case KAKAO = 'kakao';

    public static function fromString(string $provider): self
    {
        return match ($provider) {
            self::GOOGLE->value => self::GOOGLE,
            self::LINE->value => self::LINE,
            self::KAKAO->value => self::KAKAO,
            default => throw new InvalidArgumentException('Unsupported social provider'),
        };
    }
}
