<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\ConnectAccountStatus;

class ConnectAccountStatusTest extends TestCase
{
    /**
     * 正常系: すべてのステータスが定義されていること
     */
    public function testAllStatusesAreDefined(): void
    {
        $this->assertSame('pending', ConnectAccountStatus::PENDING->value);
        $this->assertSame('restricted', ConnectAccountStatus::RESTRICTED->value);
        $this->assertSame('enabled', ConnectAccountStatus::ENABLED->value);
    }

    /**
     * 正常系: ステータスの数が正しいこと
     */
    public function testStatusCount(): void
    {
        $statuses = ConnectAccountStatus::cases();
        $this->assertCount(3, $statuses);
    }

    /**
     * 正常系: 文字列からステータスを取得できること
     */
    public function testFromString(): void
    {
        $this->assertSame(ConnectAccountStatus::PENDING, ConnectAccountStatus::from('pending'));
        $this->assertSame(ConnectAccountStatus::RESTRICTED, ConnectAccountStatus::from('restricted'));
        $this->assertSame(ConnectAccountStatus::ENABLED, ConnectAccountStatus::from('enabled'));
    }

    /**
     * 正常系: 送金可能かどうかを判定できること
     */
    public function testCanReceivePayouts(): void
    {
        $this->assertFalse(ConnectAccountStatus::PENDING->canReceivePayouts());
        $this->assertFalse(ConnectAccountStatus::RESTRICTED->canReceivePayouts());
        $this->assertTrue(ConnectAccountStatus::ENABLED->canReceivePayouts());
    }
}
