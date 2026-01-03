<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\Entity;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\CountryCode;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocument;
use Source\Monetization\Billing\Domain\ValueObject\TaxDocumentType;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;

class InvoiceTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $invoiceIdentifier = new InvoiceIdentifier(StrTestHelper::generateUuid());
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $invoiceLines = [new InvoiceLine('Pro plan', new Money(500, Currency::JPY), 2)];
        $subtotal = new Money(1000, Currency::JPY);
        $discountAmount = new Money(100, Currency::JPY);
        $taxAmount = new Money(90, Currency::JPY);
        $total = new Money(990, Currency::JPY);
        $issuedAt = new DateTimeImmutable('now');
        $dueDate = new DateTimeImmutable('now +1 day');
        $status = InvoiceStatus::ISSUED;
        $paidAt = new DateTimeImmutable('now +2 day');
        $voidedAt = new DateTimeImmutable('now +3 day');
        $voidReason = 'voided';
        $taxDocument = new TaxDocument(
            TaxDocumentType::JP_QUALIFIED_INVOICE,
            CountryCode::JAPAN,
            'T-12345',
            $dueDate,
            null,
        );

        $invoice = $this->createInvoice([
            'invoiceIdentifier' => $invoiceIdentifier,
            'orderIdentifier' => $orderIdentifier,
            'buyerMonetizationAccountIdentifier' => $buyerMonetizationAccountIdentifier,
            'invoiceLines' => $invoiceLines,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'taxAmount' => $taxAmount,
            'total' => $total,
            'issuedAt' => $issuedAt,
            'dueDate' => $dueDate,
            'status' => $status,
            'paidAt' => $paidAt,
            'voidedAt' => $voidedAt,
            'voidReason' => $voidReason,
            'taxDocument' => $taxDocument,
        ]);

        $this->assertSame($invoiceIdentifier, $invoice->invoiceIdentifier());
        $this->assertSame($orderIdentifier, $invoice->orderIdentifier());
        $this->assertSame($buyerMonetizationAccountIdentifier, $invoice->buyerMonetizationAccountIdentifier());
        $this->assertSame($invoiceLines, $invoice->lines());
        $this->assertSame($total->currency(), $invoice->currency());
        $this->assertSame($subtotal, $invoice->subtotal());
        $this->assertSame($discountAmount, $invoice->discountAmount());
        $this->assertSame($taxAmount, $invoice->taxAmount());
        $this->assertSame($total, $invoice->total());
        $this->assertSame($issuedAt, $invoice->issuedAt());
        $this->assertSame($dueDate, $invoice->dueDate());
        $this->assertSame($status, $invoice->status());
        $this->assertSame($taxDocument, $invoice->taxDocument());
        $this->assertSame($paidAt, $invoice->paidAt());
        $this->assertSame($voidedAt, $invoice->voidedAt());
        $this->assertSame($voidReason, $invoice->voidReason());
    }

    /**
     * 正常系: 支払い記録でステータスがPAIDになり日時が保存されること.
     *
     * @return void
     */
    public function testRecordPayment(): void
    {
        $invoice = $this->createInvoice();

        $this->assertSame(InvoiceStatus::ISSUED, $invoice->status());
        $this->assertNull($invoice->paidAt());

        $paidAt = new DateTimeImmutable('+1 day');
        $invoice->recordPayment($invoice->total(), $paidAt);

        $this->assertSame(InvoiceStatus::PAID, $invoice->status());
        $this->assertSame($paidAt, $invoice->paidAt());
    }

    /**
     * 異常系: 請求ステータスがISSUEDでない場合は例外となること.
     *
     * @return void
     */
    public function testRecordPaymentRejectsWhenInvalidStatus(): void
    {
        $invoice = $this->createInvoice([
            'status' => InvoiceStatus::VOID,
        ]);

        $this->expectException(DomainException::class);

        $invoice->recordPayment($invoice->total(), new DateTimeImmutable());
    }

    /**
     * 異常系: 支払い通貨が一致しない場合は例外となること.
     *
     * @return void
     */
    public function testRecordPaymentRejectsWhenCurrencyDiffers(): void
    {
        $invoice = $this->createInvoice();

        $this->expectException(DomainException::class);

        $invoice->recordPayment(new Money($invoice->total()->amount(), Currency::JPY), new DateTimeImmutable());
    }

    /**
     * 異常系: 支払い金額が一致しない場合は例外となること.
     *
     * @return void
     */
    public function testRecordPaymentRejectsWhenAmountDiffers(): void
    {
        $invoice = $this->createInvoice();

        $this->expectException(DomainException::class);

        $invoice->recordPayment(new Money($invoice->total()->amount() - 1, Currency::KRW), new DateTimeImmutable());
    }

    /**
     * 正常系: voidでステータスと日時が更新されること.
     *
     * @return void
     */
    public function testVoid(): void
    {
        $invoice = $this->createInvoice();

        $this->assertSame(InvoiceStatus::ISSUED, $invoice->status());
        $this->assertNull($invoice->voidedAt());

        $voidedAt = new DateTimeImmutable('+2 days');
        $invoice->void('customer canceled', $voidedAt);

        $this->assertSame(InvoiceStatus::VOID, $invoice->status());
        $this->assertSame($voidedAt, $invoice->voidedAt());
    }

    /**
     * 異常系: 支払い後の請求書をvoidしようとすると例外となること.
     *
     * @return void
     */
    public function testCannotVoidAfterPaid(): void
    {
        $invoice = $this->createInvoice();
        $invoice->recordPayment($invoice->total(), new DateTimeImmutable());

        $this->expectException(DomainException::class);

        $invoice->void('too late', new DateTimeImmutable('+1 day'));
    }

    /**
     * 異常系: void時に理由がないと、例外となること.
     *
     * @return void
     */
    public function testCannotVoidWhenNoReason(): void
    {
        $invoice = $this->createInvoice();

        $this->expectException(InvalidArgumentException::class);

        $invoice->void('   ', new DateTimeImmutable('+1 day'));
    }

    /**
     * 異常系: 明細が空の場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenLinesEmpty(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [],
            'subtotal' => new Money(0, Currency::JPY),
            'discountAmount' => new Money(0, Currency::JPY),
            'taxAmount' => new Money(0, Currency::JPY),
            'total' => new Money(0, Currency::JPY),
        ]);
    }

    /**
     * 異常系: 明細通貨と請求通貨が異なる場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenLineCurrencyDiffers(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::KRW),
            'discountAmount' => new Money(0, Currency::KRW),
            'taxAmount' => new Money(0, Currency::KRW),
            'total' => new Money(1000, Currency::KRW),
            'issuedAt' => new DateTimeImmutable(),
            'dueDate' => new DateTimeImmutable('+1 day'),
            'status' => InvoiceStatus::ISSUED,
        ]);
    }

    /**
     * 異常系: 小計と明細合計が一致しない場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenSubtotalMismatch(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(900, Currency::JPY),
            'discountAmount' => new Money(0, Currency::JPY),
            'taxAmount' => new Money(0, Currency::JPY),
            'total' => new Money(900, Currency::JPY),
            'issuedAt' => new DateTimeImmutable(),
            'dueDate' => new DateTimeImmutable('+1 day'),
            'status' => InvoiceStatus::ISSUED,
        ]);
    }

    /**
     * 異常系: 割引通貨が一致しない場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenDiscountCurrencyDiffers(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::JPY),
            'discountAmount' => new Money(100, Currency::KRW),
            'taxAmount' => new Money(0, Currency::JPY),
            'total' => new Money(900, Currency::JPY),
        ]);
    }

    /**
     * 異常系: 割引額が小計を超える場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenDiscountExceedsSubtotal(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::JPY),
            'discountAmount' => new Money(1500, Currency::JPY),
            'taxAmount' => new Money(0, Currency::JPY),
            'total' => new Money(0, Currency::JPY),
        ]);
    }

    /**
     * 異常系: 税額通貨が一致しない場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenTaxCurrencyDiffers(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::JPY),
            'discountAmount' => new Money(0, Currency::JPY),
            'taxAmount' => new Money(90, Currency::KRW),
            'total' => new Money(1090, Currency::JPY),
        ]);
    }

    /**
     * 異常系: 総額通貨が一致しない場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenTotalCurrencyDiffers(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::JPY),
            'discountAmount' => new Money(0, Currency::JPY),
            'taxAmount' => new Money(90, Currency::JPY),
            'total' => new Money(1090, Currency::KRW),
        ]);
    }

    /**
     * 異常系: 総額がネット額より低い場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenTotalBelowNet(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::JPY),
            'discountAmount' => new Money(100, Currency::JPY),
            'taxAmount' => new Money(90, Currency::JPY),
            'total' => new Money(800, Currency::JPY),
        ]);
    }

    /**
     * 異常系: 総額がネット額+税額を超える場合は例外となること.
     *
     * @return void
     */
    public function testRejectsWhenTotalExceedsNetPlusTax(): void
    {
        $this->expectException(DomainException::class);

        $this->createInvoice([
            'invoiceLines' => [new InvoiceLine('Seat', new Money(500, Currency::JPY), 2)],
            'subtotal' => new Money(1000, Currency::JPY),
            'discountAmount' => new Money(100, Currency::JPY),
            'taxAmount' => new Money(90, Currency::JPY),
            'total' => new Money(1000, Currency::JPY),
        ]);
    }

    /**
     * @param array<string, mixed> $values
     * @return Invoice
     */
    private function createInvoice(array $values = []): Invoice
    {
        return new Invoice(
            $values['invoiceIdentifier'] ?? new InvoiceIdentifier(StrTestHelper::generateUuid()),
            $values['orderIdentifier'] ?? new OrderIdentifier(StrTestHelper::generateUuid()),
            $values['buyerMonetizationAccountIdentifier'] ?? new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            $values['invoiceLines'] ?? [new InvoiceLine('Standard plan', new Money(1000, Currency::KRW), 1)],
            $values['subtotal'] ?? new Money(1000, Currency::KRW),
            $values['discountAmount'] ?? new Money(100, Currency::KRW),
            $values['taxAmount'] ?? new Money(90, Currency::KRW),
            $values['total'] ?? new Money(990, Currency::KRW),
            $values['issuedAt'] ?? new DateTimeImmutable('now'),
            $values['dueDate'] ?? new DateTimeImmutable('now +2 day'),
            $values['status'] ?? InvoiceStatus::ISSUED,
            $values['taxDocument'] ?? null,
            $values['paidAt'] ?? null,
            $values['voidedAt'] ?? null,
            $values['voidReason'] ?? null,
        );
    }
}
