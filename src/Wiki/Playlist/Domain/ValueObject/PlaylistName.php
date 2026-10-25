<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Foundation\StringBaseValue;

class PlaylistName extends StringBaseValue
{
    public function __construct(string $name)
    {
        parent::__construct(mb_trim($name));
    }

    protected function validate(string $value): void
    {
        if (mb_strlen($value) < 1 || mb_strlen($value) > 32) {
            throw new InvalidArgumentException('Playlist name must contain 1 to 32 characters.');
        }
    }
}
