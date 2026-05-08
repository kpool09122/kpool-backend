<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Query;

trait ArrayAccessibleReadModel
{
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->toArray());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->toArray()[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('ReadModel is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('ReadModel is immutable.');
    }
}
