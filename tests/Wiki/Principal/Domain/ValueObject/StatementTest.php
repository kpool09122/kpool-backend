<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\Condition;
use Source\Wiki\Principal\Domain\ValueObject\ConditionClause;
use Source\Wiki\Principal\Domain\ValueObject\ConditionKey;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;
use Source\Wiki\Principal\Domain\ValueObject\ConditionValue;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class StatementTest extends TestCase
{
    /**
     * 正常系: Condition を使用したインスタンス生成
     */
    public function test__construct_with_condition(): void
    {
        $effect = Effect::ALLOW;
        $actions = [Action::APPROVE, Action::REJECT];
        $resourceTypes = [ResourceType::AGENCY];
        $condition = new Condition([
            new ConditionClause(
                ConditionKey::RESOURCE_AGENCY_ID,
                ConditionOperator::EQUALS,
                ConditionValue::PRINCIPAL_AGENCY_ID,
            ),
        ]);

        $statement = new Statement(
            effect: $effect,
            actions: $actions,
            resourceTypes: $resourceTypes,
            condition: $condition,
        );

        $this->assertSame($effect, $statement->effect());
        $this->assertSame($actions, $statement->actions());
        $this->assertSame($resourceTypes, $statement->resourceTypes());
        $this->assertSame($condition, $statement->condition());
    }

    /**
     * 正常系: Condition が null の場合（制約なし）
     */
    public function test__construct_with_null_condition(): void
    {
        $effect = Effect::ALLOW;
        $actions = Action::cases();
        $resourceTypes = ResourceType::cases();

        $statement = new Statement(
            effect: $effect,
            actions: $actions,
            resourceTypes: $resourceTypes,
            condition: null,
        );

        $this->assertSame($effect, $statement->effect());
        $this->assertNull($statement->condition());
    }

    /**
     * 正常系: Condition を省略した場合（デフォルト null）
     */
    public function test__construct_without_condition(): void
    {
        $effect = Effect::DENY;
        $actions = [Action::ROLLBACK];
        $resourceTypes = ResourceType::cases();

        $statement = new Statement(
            effect: $effect,
            actions: $actions,
            resourceTypes: $resourceTypes,
        );

        $this->assertSame($effect, $statement->effect());
        $this->assertSame($actions, $statement->actions());
        $this->assertSame($resourceTypes, $statement->resourceTypes());
        $this->assertNull($statement->condition());
    }
}
