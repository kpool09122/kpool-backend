<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ExternalVideoId extends StringBaseValue
{
    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->validate($value);
    }

    public static function isValidYouTubeId(string $value): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]{11}$/D', $value) === 1;
    }

    public static function isValidTikTokId(string $value): bool
    {
        return preg_match('/^[1-9][0-9]{14,19}$/D', $value) === 1;
    }

    protected function validate(string $value): void
    {
        if (! self::isValidYouTubeId($value) && ! self::isValidTikTokId($value)) {
            throw new InvalidArgumentException('Invalid external video identifier.');
        }
    }
}
