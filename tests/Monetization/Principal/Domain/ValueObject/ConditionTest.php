<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\Condition;
use Source\Monetization\Principal\Domain\ValueObject\ConditionClause;
use Source\Monetization\Principal\Domain\ValueObject\ConditionKey;
use Source\Monetization\Principal\Domain\ValueObject\ConditionOperator;

class ConditionTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $clauses = [new ConditionClause(ConditionKey::cases()[0], ConditionOperator::EQUALS, 'account-a')];

        $subject = new Condition($clauses);

        $this->assertSame($clauses, $subject->clauses());
    }
}
