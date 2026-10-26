<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;

final readonly class PlaylistItemPosition
{
    public function __construct(private int $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Playlist item position must be non-negative.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
