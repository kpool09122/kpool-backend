<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\Condition;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;

class ConditionTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $clauses = [new ConditionClause(ConditionKey::cases()[0], ConditionOperator::cases()[0], true)];

        $subject = new Condition($clauses);

        $this->assertSame($clauses, $subject->clauses());
    }
}
