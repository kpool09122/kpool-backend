<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

class ConditionTest extends TestCase
{
    /**
     * 正常系: 単一のClauseを持つCondition
     */
    public function test_construct_with_single_clause(): void
    {
        $clause = new ConditionClause(
            ConditionKey::RESOURCE_AGENCY_ID,
            ConditionOperator::EQUALS,
            ConditionValue::PRINCIPAL_AGENCY_ID,
        );

        $condition = new Condition([$clause]);

        $this->assertCount(1, $condition->clauses());
        $this->assertSame($clause, $condition->clauses()[0]);
    }

    /**
     * 正常系: 複数のClauseを持つCondition（AND結合）
     */
    public function test_construct_with_multiple_clauses(): void
    {
        $clause1 = new ConditionClause(
            ConditionKey::RESOURCE_AGENCY_ID,
            ConditionOperator::EQUALS,
            ConditionValue::PRINCIPAL_AGENCY_ID,
        );
        $clause2 = new ConditionClause(
            ConditionKey::RESOURCE_IS_OFFICIAL,
            ConditionOperator::EQUALS,
            true,
        );

        $condition = new Condition([$clause1, $clause2]);

        $this->assertCount(2, $condition->clauses());
        $this->assertSame($clause1, $condition->clauses()[0]);
        $this->assertSame($clause2, $condition->clauses()[1]);
    }

    /**
     * 正常系: 空のClause配列を持つCondition
     */
    public function test_construct_with_empty_clauses(): void
    {
        $condition = new Condition([]);

        $this->assertCount(0, $condition->clauses());
    }
}
