<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Domain\ValueObject;

final readonly class Condition
{
    /**
     * @param list<ConditionClause> $clauses AND結合で評価
     */
    public function __construct(
        private array $clauses,
    ) {
    }

    /**
     * @return list<ConditionClause>
     */
    public function clauses(): array
    {
        return $this->clauses;
    }
}
