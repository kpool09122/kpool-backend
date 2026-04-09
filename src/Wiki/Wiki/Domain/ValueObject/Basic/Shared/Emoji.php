<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;

final readonly class Emoji
{
    private const int MAX_LENGTH = 16;

    public function __construct(
        private string $value,
    ) {
        if ($value === '') {
            return;
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Emoji must be %d characters or less.', self::MAX_LENGTH)
            );
        }

        if (! self::isValidEmoji($value)) {
            throw new InvalidArgumentException('Emoji must contain only valid Unicode emoji characters.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    private static function isValidEmoji(string $value): bool
    {
        // Remove variation selectors and zero-width joiners for validation
        $cleaned = preg_replace('/[\x{FE0F}\x{200D}]/u', '', $value);

        // Check if all remaining characters are emoji
        // This pattern matches emoji characters including:
        // - Basic emoticons (1F600-1F64F)
        // - Miscellaneous symbols (2600-26FF)
        // - Dingbats (2700-27BF)
        // - Transport and map symbols (1F680-1F6FF)
        // - Supplemental symbols (1F900-1F9FF)
        // - Flags (1F1E0-1F1FF)
        // - Various other emoji blocks
        $pattern = '/^[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F000}-\x{1F02F}\x{1F0A0}-\x{1F0FF}\x{1F100}-\x{1F1FF}\x{1FA00}-\x{1FAFF}\x{2300}-\x{23FF}\x{2B50}-\x{2B55}\x{203C}\x{2049}\x{20E3}\x{00A9}\x{00AE}]+$/u';

        return preg_match($pattern, (string) $cleaned) === 1;
    }
}
