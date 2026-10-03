<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;

class PaymentStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'PENDING' => 'pending',
            'AUTHORIZED' => 'authorized',
            'CAPTURED' => 'captured',
            'PARTIALLY_REFUNDED' => 'partially_refunded',
            'REFUNDED' => 'refunded',
            'FAILED' => 'failed',
        ], array_column(PaymentStatus::cases(), 'value', 'name'));
    }
}
