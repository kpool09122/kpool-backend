<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\Percentage;
use Source\Monetization\Billing\Domain\ValueObject\TaxLine;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class TaxLineTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $label = 'VAT';
        $rate = new Percentage(10);
        $taxLine = new TaxLine($label, $rate, true);
        $this->assertSame($label, $taxLine->label());
        $this->assertSame($rate, $taxLine->rate());
        $this->assertTrue($taxLine->isInclusive());
    }

    /**
     * 異常系: ラベルが空の場合、例外がスローされること.
     *
     * @return void
     */
    public function testRejectsEmptyLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TaxLine('', new Percentage(10), false);
    }

    /**
     * 正常系: 税抜き時の税額を正しく計算できること.
     *
     * @return void
     */
    public function testCalculatesExclusiveTax(): void
    {
        $taxLine = new TaxLine('VAT', new Percentage(10), false);

        $taxAmount = $taxLine->taxAmountFor(new Money(1000, Currency::JPY));

        $this->assertSame(100, $taxAmount->amount());
    }

    /**
     * 正常系: 税込み時の税額を正しく計算できること.
     *
     * @return void
     */
    public function testCalculatesInclusiveTax(): void
    {
        $taxLine = new TaxLine('VAT', new Percentage(10), true);

        $taxAmount = $taxLine->taxAmountFor(new Money(1100, Currency::JPY));

        $this->assertSame(100, $taxAmount->amount());
    }
}
