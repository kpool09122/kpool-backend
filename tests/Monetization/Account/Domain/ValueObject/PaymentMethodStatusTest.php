<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;

class PaymentMethodStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACTIVE' => 'active',
            'INACTIVE' => 'inactive',
        ], array_column(PaymentMethodStatus::cases(), 'value', 'name'));
    }
}
