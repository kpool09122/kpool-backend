<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

trait ArrayAccessibleReadModel
{
    public function offsetExists(mixed $offset): bool
    {
        return (is_int($offset) || is_string($offset)) && array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! is_int($offset) && ! is_string($offset)) {
            throw new InvalidArgumentException('Array offset must be a string or integer.');
        }
        $array = $this->toArray();
        if (! array_key_exists($offset, $array)) {
            throw new OutOfBoundsException('Unknown read model offset.');
        }

        return $array[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('ReadModel is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('ReadModel is immutable.');
    }
}
