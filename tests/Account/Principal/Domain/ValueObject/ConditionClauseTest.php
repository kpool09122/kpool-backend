<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Domain\ValueObject\ConditionClause;
use Source\Account\Principal\Domain\ValueObject\ConditionKey;
use Source\Account\Principal\Domain\ValueObject\ConditionOperator;

class ConditionClauseTest extends TestCase
{
    public function testPreservesSuppliedValues(): void
    {
        $key = ConditionKey::RESOURCE_ACCOUNT_TYPE;
        $operator = ConditionOperator::EQUALS;
        $value = 'value-value';

        $subject = new ConditionClause($key, $operator, $value);

        $this->assertSame($key, $subject->key());
        $this->assertSame($operator, $subject->operator());
        $this->assertSame($value, $subject->value());
    }

    public function testPreservesBooleanAndListConditions(): void
    {
        foreach ([true, false, ['first', 'second']] as $value) {
            $clause = new ConditionClause(ConditionKey::cases()[0], ConditionOperator::cases()[0], $value);
            $this->assertSame($value, $clause->value());
        }
    }
}
