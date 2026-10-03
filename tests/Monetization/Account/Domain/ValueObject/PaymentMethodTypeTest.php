<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;

class PaymentMethodTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'CARD' => 'card',
        ], array_column(PaymentMethodType::cases(), 'value', 'name'));
    }
}
