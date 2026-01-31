<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Domain\ValueObject\ReplyStatus;
use ValueError;

class ReplyStatusTest extends TestCase
{
    /**
     * 正常系: ステータスが期待通りに解決できること
     */
    public function testValue(): void
    {
        $this->assertSame(0, ReplyStatus::UNSENT->value);
        $this->assertSame(1, ReplyStatus::SENT->value);
        $this->assertSame(2, ReplyStatus::FAILED->value);
    }

    /**
     * 異常系: 未定義のステータスは ValueError になること
     */
    public function testFromThrowsWhenUndefinedValue(): void
    {
        $this->expectException(ValueError::class);
        ReplyStatus::from(3);
    }
}
