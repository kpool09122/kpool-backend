<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

final readonly class ConditionClause
{
    public function __construct(
        private ConditionKey $key,
        private ConditionOperator $operator,
        private ConditionValue|string|bool $value,
    ) {
    }

    public function key(): ConditionKey
    {
        return $this->key;
    }

    public function operator(): ConditionOperator
    {
        return $this->operator;
    }

    public function value(): ConditionValue|string|bool
    {
        return $this->value;
    }
}
