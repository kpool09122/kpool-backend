<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;

class InvoiceStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ISSUED' => 'issued',
            'PAID' => 'paid',
            'VOID' => 'void',
        ], array_column(InvoiceStatus::cases(), 'value', 'name'));
    }
}
