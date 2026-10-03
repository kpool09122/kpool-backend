<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\BillingMethod;

class BillingMethodTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'INVOICE' => 'invoice',
            'CREDIT_CARD' => 'credit_card',
            'BANK_TRANSFER' => 'bank_transfer',
        ], array_column(BillingMethod::cases(), 'value', 'name'));
    }
}
