<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\ValueObject\ConditionOperator;

class ConditionOperatorTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'EQUALS' => 'eq',
            'NOT_EQUALS' => 'ne',
            'IN' => 'in',
            'NOT_IN' => 'not_in',
        ], array_column(ConditionOperator::cases(), 'value', 'name'));
    }
}
