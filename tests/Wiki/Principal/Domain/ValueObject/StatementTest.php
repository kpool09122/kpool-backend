<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\Effect;
use Source\Wiki\Principal\Domain\ValueObject\ScopeCondition;
use Source\Wiki\Principal\Domain\ValueObject\Statement;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

class StatementTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $effect = Effect::ALLOW;
        $actions = [Action::CREATE, Action::EDIT, Action::SUBMIT];
        $resourceTypes = [ResourceType::GROUP, ResourceType::TALENT];
        $condition = ScopeCondition::OWN_GROUPS;
        $statement = new Statement(
            $effect,
            $actions,
            $resourceTypes,
            $condition,
        );
        $this->assertSame($effect, $statement->effect());
        $this->assertSame($actions, $statement->actions());
        $this->assertSame($resourceTypes, $statement->resourceTypes());
        $this->assertSame($condition, $statement->scopeCondition());
    }
}
