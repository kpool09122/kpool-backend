<?php

declare(strict_types=1);

namespace Source\Account\Principal\Domain\ValueObject;

final readonly class ConditionClause
{
    /** @param string|bool|list<string> $value */
    public function __construct(
        private ConditionKey $key,
        private ConditionOperator $operator,
        private string|bool|array $value,
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

    /** @return string|bool|list<string> */
    public function value(): string|bool|array
    {
        return $this->value;
    }
}
