<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\ValueObject\Action;
use Source\Analyze\Principal\Domain\ValueObject\ConditionInterface;
use Source\Analyze\Principal\Domain\ValueObject\Effect;
use Source\Analyze\Principal\Domain\ValueObject\ResourceType;
use Source\Analyze\Principal\Domain\ValueObject\Statement;

class StatementTest extends TestCase
{
    public function testPreservesCondition(): void
    {
        $condition = $this->createStub(ConditionInterface::class);
        $statement = new Statement(Effect::ALLOW, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS], $condition);

        self::assertSame($condition, $statement->condition());
    }

    public function testPreservesPolicyVocabulary(): void
    {
        $statement = new Statement(Effect::DENY, [Action::ANALYSIS_VIEW], [ResourceType::ANALYSIS]);
        self::assertNull($statement->condition());
        self::assertSame(Effect::DENY, $statement->effect());
        self::assertSame([Action::ANALYSIS_VIEW], $statement->actions());
        self::assertSame([ResourceType::ANALYSIS], $statement->resourceTypes());
    }
}
