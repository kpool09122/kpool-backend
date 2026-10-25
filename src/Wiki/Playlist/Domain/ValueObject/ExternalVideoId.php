<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class ExternalVideoId extends StringBaseValue
{
    public function __construct(string $value)
    {
        parent::__construct($value);
        $this->validate($value);
    }

    protected function validate(string $value): void
    {
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/D', $value) !== 1) {
            throw new InvalidArgumentException('Invalid external video identifier.');
        }
    }
}
