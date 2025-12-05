<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class InvoiceLineTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $description = 'Subscription';
        $unitPrice = new Money(1500, Currency::JPY);
        $quantity = 1;
        $invoiceLine = new InvoiceLine($description, $unitPrice, $quantity);
        $this->assertSame($description, $invoiceLine->description());
        $this->assertSame($unitPrice, $invoiceLine->unitPrice());
        $this->assertSame($quantity, $invoiceLine->quantity());
    }

    /**
     * 正常系: 明細の合計が正しく計算されること.
     *
     * @return void
     */
    public function testCalculatesLineTotal(): void
    {
        $line = new InvoiceLine('Subscription', new Money(1500, Currency::JPY), 3);

        $this->assertSame(4500, $line->lineTotal()->amount());
        $this->assertSame(Currency::JPY, $line->lineTotal()->currency());
    }

    /**
     * 異常系: 説明が空の時、例外がスローされること.
     *
     * @return void
     */
    public function testRejectsEmptyDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InvoiceLine(' ', new Money(1000, Currency::JPY), 1);
    }

    /**
     * 異常系: 数量が0の時、例外がスローされること.
     *
     * @return void
     */
    public function testRejectsZeroQuantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InvoiceLine('Seat', new Money(1000, Currency::JPY), 0);
    }
}
