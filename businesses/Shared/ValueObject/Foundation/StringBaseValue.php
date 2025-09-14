<?php

declare(strict_types=1);

namespace Businesses\Shared\ValueObject\Foundation;

use InvalidArgumentException;
use Stringable;

abstract class StringBaseValue implements Stringable
{
    public function __construct(
        protected readonly string $value
    ) {
        $this->validate($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @throws InvalidArgumentException
     */
    abstract protected function validate(string $value): void;
}
