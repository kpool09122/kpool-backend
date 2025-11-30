<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\Currency;
use Source\Account\Domain\ValueObject\Money;

class MoneyTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $amount = 10000;
        $currency = Currency::KRW;
        $money = new Money($amount, $currency);

        $this->assertSame($amount, $money->amount());
        $this->assertSame($currency, $money->currency());
    }

    /**
     * 異常系: amountが負の値の時、例外がスローされること.
     *
     * @return void
     */
    public function testWhenAmountIsMinus(): void
    {
        $amount = -10000;
        $currency = Currency::KRW;
        $this->expectException(InvalidArgumentException::class);
        new Money($amount, $currency);
    }
}
