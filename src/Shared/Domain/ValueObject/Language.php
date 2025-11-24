<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

enum Language: string
{
    case JAPANESE = 'ja';
    case KOREAN = 'ko';
    case ENGLISH = 'en';

    /**
     * @param Language $excluded
     * @return Language[]
     */
    public static function allExcept(self $excluded): array
    {
        return array_values(array_filter(self::cases(), static fn (self $case): bool => $case !== $excluded));
    }
}
