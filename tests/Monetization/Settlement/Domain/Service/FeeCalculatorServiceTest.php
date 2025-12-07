<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\Service;

use DomainException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Settlement\Domain\Service\FeeCalculatorServiceInterface;
use Source\Monetization\Shared\ValueObject\Percentage;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Tests\TestCase;

class FeeCalculatorServiceTest extends TestCase
{
    /**
     * 正常系: 手数料がゲートウェイ + プラットフォーム + 固定費で合算されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCalculate(): void
    {
        $calculator = $this->app->make(FeeCalculatorServiceInterface::class);
        $gross = new Money(10000, Currency::JPY);

        $fee = $calculator->calculate(
            $gross,
            new Percentage(3),
            new Percentage(5),
            new Money(100, Currency::JPY)
        );

        $this->assertSame(900, $fee->amount());
        $this->assertSame($gross->currency(), $fee->currency());
    }

    /**
     * 異常系: 通貨が異なる固定費を渡すと例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testRejectsMismatchedCurrency(): void
    {
        $calculator = $this->app->make(FeeCalculatorServiceInterface::class);

        $this->expectException(DomainException::class);

        $calculator->calculate(
            new Money(10000, Currency::JPY),
            new Percentage(3),
            new Percentage(5),
            new Money(100, Currency::USD)
        );
    }

    /**
     * 異常系: 手数料合計が売上を超える場合は例外となること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testRejectsFeeExceedingGross(): void
    {
        $calculator = $this->app->make(FeeCalculatorServiceInterface::class);

        $this->expectException(DomainException::class);

        $calculator->calculate(
            new Money(100, Currency::KRW),
            new Percentage(90),
            new Percentage(20),
            null
        );
    }
}
