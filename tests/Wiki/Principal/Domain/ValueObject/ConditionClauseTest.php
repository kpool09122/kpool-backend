<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;

class ConditionClauseTest extends TestCase
{
    /**
     * 正常系: ConditionValue を使用したインスタンス生成
     */
    public function test_construct_with_condition_value(): void
    {
        $clause = new ConditionClause(
            ConditionKey::RESOURCE_AGENCY_ID,
            ConditionOperator::EQUALS,
            ConditionValue::PRINCIPAL_AGENCY_ID,
        );

        $this->assertSame(ConditionKey::RESOURCE_AGENCY_ID, $clause->key());
        $this->assertSame(ConditionOperator::EQUALS, $clause->operator());
        $this->assertSame(ConditionValue::PRINCIPAL_AGENCY_ID, $clause->value());
    }

    /**
     * 正常系: 文字列リテラルを使用したインスタンス生成
     */
    public function test_construct_with_string_literal(): void
    {
        $clause = new ConditionClause(
            ConditionKey::RESOURCE_AGENCY_ID,
            ConditionOperator::EQUALS,
            'agency-123',
        );

        $this->assertSame(ConditionKey::RESOURCE_AGENCY_ID, $clause->key());
        $this->assertSame(ConditionOperator::EQUALS, $clause->operator());
        $this->assertSame('agency-123', $clause->value());
    }

    /**
     * 正常系: 真偽値リテラルを使用したインスタンス生成
     */
    public function test_construct_with_bool_literal(): void
    {
        $clause = new ConditionClause(
            ConditionKey::RESOURCE_IS_OFFICIAL,
            ConditionOperator::EQUALS,
            true,
        );

        $this->assertSame(ConditionKey::RESOURCE_IS_OFFICIAL, $clause->key());
        $this->assertSame(ConditionOperator::EQUALS, $clause->operator());
        $this->assertTrue($clause->value());
    }

    /**
     * 正常系: IN演算子を使用したインスタンス生成
     */
    public function test_construct_with_in_operator(): void
    {
        $clause = new ConditionClause(
            ConditionKey::RESOURCE_GROUP_ID,
            ConditionOperator::IN,
            ConditionValue::PRINCIPAL_WIKI_GROUP_IDS,
        );

        $this->assertSame(ConditionKey::RESOURCE_GROUP_ID, $clause->key());
        $this->assertSame(ConditionOperator::IN, $clause->operator());
        $this->assertSame(ConditionValue::PRINCIPAL_WIKI_GROUP_IDS, $clause->value());
    }
}
