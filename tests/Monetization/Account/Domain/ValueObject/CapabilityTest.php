<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\Capability;

class CapabilityTest extends TestCase
{
    /**
     * 正常系: すべてのCapabilityが定義されていること
     */
    public function testAllCapabilitiesAreDefined(): void
    {
        $this->assertSame('purchase', Capability::PURCHASE->value);
        $this->assertSame('sell', Capability::SELL->value);
        $this->assertSame('receive_payout', Capability::RECEIVE_PAYOUT->value);
    }

    /**
     * 正常系: Capabilityの数が正しいこと
     */
    public function testCapabilityCount(): void
    {
        $capabilities = Capability::cases();
        $this->assertCount(3, $capabilities);
    }

    /**
     * 正常系: 文字列からCapabilityを取得できること
     */
    public function testFromString(): void
    {
        $this->assertSame(Capability::PURCHASE, Capability::from('purchase'));
        $this->assertSame(Capability::SELL, Capability::from('sell'));
        $this->assertSame(Capability::RECEIVE_PAYOUT, Capability::from('receive_payout'));
    }
}
