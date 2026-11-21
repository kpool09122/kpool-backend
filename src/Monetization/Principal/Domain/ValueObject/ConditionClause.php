<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Domain\ValueObject;

final readonly class ConditionClause
{
    /** @param string|list<string> $value */
    public function __construct(
        private ConditionKey $key,
        private ConditionOperator $operator,
        private string|array $value,
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

    /** @return string|list<string> */
    public function value(): string|array
    {
        return $this->value;
    }
}
