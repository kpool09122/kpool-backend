<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;

final readonly class RepresentativeSymbol
{
    private const int MAX_LENGTH = 32;

    public function __construct(
        private string $value,
    ) {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Representative symbol must be %d characters or less.', self::MAX_LENGTH)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
