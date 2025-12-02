<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

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

        $otherAmount = 20000;
        $sameCurrency = Currency::KRW;
        $otherMoney = new Money($otherAmount, $sameCurrency);

        $this->assertTrue($money->isSameCurrency($otherMoney));
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

    /**
     * 正常系: addメソッドで金額を加算した新しいインスタンスが作成されること.
     *
     * @return void
     */
    public function testAdd(): void
    {
        $amount = 10000;
        $currency = Currency::KRW;
        $money = new Money($amount, $currency);

        $otherAmount = 20000;
        $sameCurrency = Currency::KRW;
        $otherMoney = new Money($otherAmount, $sameCurrency);

        $addedMoney = $money->add($otherMoney);
        $this->assertSame($amount + $otherAmount, $addedMoney->amount());
    }

    /**
     * 異常系: addメソッドで異なる通貨の場合、例外がスローされること.
     *
     * @return void
     */
    public function testAddWhenDifferentCurrency(): void
    {
        $amount = 10000;
        $currency = Currency::KRW;
        $money = new Money($amount, $currency);

        $otherAmount = 20000;
        $sameCurrency = Currency::JPY;
        $otherMoney = new Money($otherAmount, $sameCurrency);

        $this->expectException(DomainException::class);
        $money->add($otherMoney);
    }

    /**
     * 正常系: subtractメソッドで金額を減算した新しいインスタンスが作成されること.
     *
     * @return void
     */
    public function testSubtract(): void
    {
        $amount = 20000;
        $currency = Currency::KRW;
        $money = new Money($amount, $currency);

        $otherAmount = 10000;
        $sameCurrency = Currency::KRW;
        $otherMoney = new Money($otherAmount, $sameCurrency);

        $addedMoney = $money->subtract($otherMoney);
        $this->assertSame($amount - $otherAmount, $addedMoney->amount());
    }

    /**
     * 異常系: subtractメソッドで異なる通貨の場合、例外がスローされること.
     *
     * @return void
     */
    public function testSubtractWhenDifferentCurrency(): void
    {
        $amount = 20000;
        $currency = Currency::KRW;
        $money = new Money($amount, $currency);

        $otherAmount = 10000;
        $sameCurrency = Currency::JPY;
        $otherMoney = new Money($otherAmount, $sameCurrency);

        $this->expectException(DomainException::class);
        $money->subtract($otherMoney);
    }

    /**
     * 異常系: 減算の結果0未満になった場合、例外がスローされること.
     *
     * @return void
     */
    public function testSubtractWhenMinus(): void
    {
        $amount = 10000;
        $currency = Currency::KRW;
        $money = new Money($amount, $currency);

        $otherAmount = 20000;
        $sameCurrency = Currency::KRW;
        $otherMoney = new Money($otherAmount, $sameCurrency);

        $this->expectException(DomainException::class);
        $money->subtract($otherMoney);
    }
}
