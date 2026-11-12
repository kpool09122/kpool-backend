<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

enum ProfileCardShape: string
{
    case PORTRAIT = 'portrait';
    case SQUARE = 'square';
    case LANDSCAPE = 'landscape';

    public static function fromValue(mixed $value): self
    {
        return (is_string($value) ? self::tryFrom($value) : null) ?? self::PORTRAIT;
    }
}
