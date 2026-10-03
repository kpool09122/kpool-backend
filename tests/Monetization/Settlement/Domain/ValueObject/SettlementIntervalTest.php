<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;

class SettlementIntervalTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'MONTHLY' => 'monthly',
            'BIWEEKLY' => 'biweekly',
            'THRESHOLD' => 'threshold',
        ], array_column(SettlementInterval::cases(), 'value', 'name'));
    }
}
