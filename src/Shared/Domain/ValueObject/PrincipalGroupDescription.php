<?php

declare(strict_types=1);

namespace Source\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class PrincipalGroupDescription implements Stringable
{
    public const int MAX_LENGTH = 256;

    public function __construct(private string $value)
    {
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Principal group description cannot exceed 256 characters.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
