<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;

class PaymentMethodTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'CARD' => 'card',
            'BANK_TRANSFER' => 'bank_transfer',
            'WALLET' => 'wallet',
        ], array_column(PaymentMethodType::cases(), 'value', 'name'));
    }
}
