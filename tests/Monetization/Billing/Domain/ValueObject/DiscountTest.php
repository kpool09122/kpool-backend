<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\Discount;
use Source\Monetization\Billing\Domain\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class DiscountTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $percentage = new Percentage(50);
        $code = 'ABC0041456';
        $discount = new Discount($percentage, $code);
        $this->assertSame($percentage, $discount->percentage());
        $this->assertSame($code, $discount->code());
    }

    /**
     * 正常系: 正しく割引計算できること.
     *
     * @return void
     */
    public function testAmountFor(): void
    {
        $discount = new Discount(new Percentage(50));
        $base = new Money(2000, Currency::KRW);
        $discounted = $discount->amountFor($base);
        $this->assertSame(1000, $discounted->amount());
        $this->assertSame(Currency::KRW, $discounted->currency());
    }

    /**
     * 正常系: パーセンテージを正しく適用できること.
     *
     * @return void
     */
    public function testApply(): void
    {
        $discount = new Discount(new Percentage(15), 'WELCOME15');
        $base = new Money(2000, Currency::JPY);
        $applied = $discount->apply($base);

        $this->assertSame(1700, $applied->amount());
        $this->assertSame(Currency::JPY, $applied->currency());
    }
}
