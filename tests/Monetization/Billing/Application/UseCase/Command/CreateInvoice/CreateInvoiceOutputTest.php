<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Application\UseCase\Command\CreateInvoice;

use DateTimeImmutable;
use DateTimeInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Billing\Application\UseCase\Command\CreateInvoice\CreateInvoiceOutput;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class CreateInvoiceOutputTest extends TestCase
{
    /**
     * 正常系: InvoiceがセットされるとtoArrayが正しい値を返すこと.
     */
    public function testToArrayWithInvoice(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-15T10:00:00+00:00');
        $dueDate = $issuedAt->modify('+14 days');

        $invoice = new Invoice(
            new InvoiceIdentifier(StrTestHelper::generateUuid()),
            new OrderIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            [new InvoiceLine('Test Product', new Money(1000, Currency::JPY), 1)],
            new Money(1000, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(1000, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
        );

        $output = new CreateInvoiceOutput();
        $output->setInvoice($invoice);

        $result = $output->toArray();

        $this->assertSame((string) $invoice->invoiceIdentifier(), $result['invoiceIdentifier']);
        $this->assertSame((string) $invoice->orderIdentifier(), $result['orderIdentifier']);
        $this->assertSame((string) $invoice->buyerMonetizationAccountIdentifier(), $result['buyerMonetizationAccountIdentifier']);
        $this->assertSame(1000, $result['subtotal']);
        $this->assertSame(0, $result['discountAmount']);
        $this->assertSame(0, $result['taxAmount']);
        $this->assertSame(1000, $result['total']);
        $this->assertSame('JPY', $result['currency']);
        $this->assertSame('issued', $result['status']);
        $this->assertSame($issuedAt->format(DateTimeInterface::ATOM), $result['issuedAt']);
        $this->assertSame($dueDate->format(DateTimeInterface::ATOM), $result['dueDate']);
        $this->assertNull($result['paidAt']);
    }

    /**
     * 正常系: Invoiceが未セットの場合toArrayが空配列を返すこと.
     */
    public function testToArrayWithoutInvoice(): void
    {
        $output = new CreateInvoiceOutput();
        $this->assertSame([], $output->toArray());
    }

    /**
     * 正常系: 支払い済みInvoiceのpaidAtが含まれること.
     */
    public function testToArrayIncludesPaidAt(): void
    {
        $issuedAt = new DateTimeImmutable('2024-01-15T10:00:00+00:00');
        $dueDate = $issuedAt->modify('+14 days');

        $invoice = new Invoice(
            new InvoiceIdentifier(StrTestHelper::generateUuid()),
            new OrderIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            [new InvoiceLine('Test Product', new Money(1000, Currency::JPY), 1)],
            new Money(1000, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(0, Currency::JPY),
            new Money(1000, Currency::JPY),
            $issuedAt,
            $dueDate,
            InvoiceStatus::ISSUED,
        );

        $paidAt = new DateTimeImmutable('2024-01-20T12:00:00+00:00');
        $invoice->recordPayment($invoice->total(), $paidAt);

        $output = new CreateInvoiceOutput();
        $output->setInvoice($invoice);

        $result = $output->toArray();

        $this->assertSame('paid', $result['status']);
        $this->assertSame($paidAt->format(DateTimeInterface::ATOM), $result['paidAt']);
    }
}
