<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\BillingPeriod;

class BillingPeriodTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'MONTHLY' => 'monthly',
            'QUARTERLY' => 'quarterly',
            'ANNUAL' => 'annual',
        ], array_column(BillingPeriod::cases(), 'value', 'name'));
    }
}
