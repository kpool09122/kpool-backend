<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementStatus;

class SettlementStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'PENDING' => 'pending',
            'PROCESSING' => 'processing',
            'PAID' => 'paid',
            'FAILED' => 'failed',
        ], array_column(SettlementStatus::cases(), 'value', 'name'));
    }
}
