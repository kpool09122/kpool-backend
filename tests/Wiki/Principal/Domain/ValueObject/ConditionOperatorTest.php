<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\ConditionOperator;

class ConditionOperatorTest extends TestCase
{
    /**
     * 正常系: 全てのケースが定義されていること
     */
    public function test_all_cases_are_defined(): void
    {
        $cases = ConditionOperator::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(ConditionOperator::EQUALS, $cases);
        $this->assertContains(ConditionOperator::NOT_EQUALS, $cases);
        $this->assertContains(ConditionOperator::IN, $cases);
        $this->assertContains(ConditionOperator::NOT_IN, $cases);
    }

    /**
     * 正常系: 各ケースの値が正しいこと
     */
    public function test_case_values(): void
    {
        $this->assertSame('eq', ConditionOperator::EQUALS->value);
        $this->assertSame('ne', ConditionOperator::NOT_EQUALS->value);
        $this->assertSame('in', ConditionOperator::IN->value);
        $this->assertSame('not_in', ConditionOperator::NOT_IN->value);
    }
}
