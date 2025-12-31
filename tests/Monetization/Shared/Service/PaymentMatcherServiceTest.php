<?php

declare(strict_types=1);

namespace Tests\Monetization\Shared\Service;

use DateTimeImmutable;
use DomainException;
use Exception;
use Source\Monetization\Billing\Domain\Entity\Invoice;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceIdentifier;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceLine;
use Source\Monetization\Billing\Domain\ValueObject\InvoiceStatus;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Shared\Service\PaymentMatcherServiceInterface;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Source\Shared\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentMatcherServiceTest extends TestCase
{
    /**
     * 正常系: 正しく支払いと請求書を一致させられること.
     *
     * @return void
     * @throws Exception
     */
    public function testMarksInvoicePaidWhenCapturedPaymentMatches(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $invoice = $this->createInvoice($orderIdentifier);
        $payment = $this->createCapturedPayment($orderIdentifier, $invoice->total());
        $paidAt = new DateTimeImmutable('+1 minute');

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);
        $matcher->match($invoice, $payment, $paidAt);

        $this->assertSame(InvoiceStatus::PAID, $invoice->status());
        $this->assertSame($paidAt, $invoice->paidAt());
    }

    /**
     * 異常系: 請求書と支払いの注文識別子が異なる場合、例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testRejectsWhenOrderIdentifiersDiffer(): void
    {
        $invoiceOrderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $paymentOrderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $invoice = $this->createInvoice($invoiceOrderIdentifier);
        $payment = $this->createCapturedPayment($paymentOrderIdentifier, $invoice->total());

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);

        $this->expectException(DomainException::class);
        $matcher->match($invoice, $payment, new DateTimeImmutable('+1 minute'));
    }

    /**
     * 異常系: 支払いステータスが確定済みでない場合、例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testRejectsWhenPaymentStatusIsNotCaptured(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $invoice = $this->createInvoice($orderIdentifier);
        $payment = $this->createCapturedPayment($orderIdentifier, $invoice->total(), PaymentStatus::AUTHORIZED);

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);

        $this->expectException(DomainException::class);
        $matcher->match($invoice, $payment, new DateTimeImmutable('+1 minute'));
    }

    /**
     * 異常系: 支払いと請求額が異なる時、例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testRejectsWhenPaymentAmountDiffers(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $invoice = $this->createInvoice($orderIdentifier);
        $payment = $this->createCapturedPayment($orderIdentifier, new Money($invoice->total()->amount() - 10, Currency::JPY));

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);

        $this->expectException(DomainException::class);
        $matcher->match($invoice, $payment, new DateTimeImmutable());
    }

    /**
     * 異常系: 支払いと請求書の通貨が異なる時、例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testRejectsWhenPaymentCurrencyDiffers(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $invoice = $this->createInvoice($orderIdentifier);
        $payment = $this->createCapturedPayment($orderIdentifier, new Money($invoice->total()->amount(), Currency::KRW));

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);

        $this->expectException(DomainException::class);
        $matcher->match($invoice, $payment, new DateTimeImmutable());
    }

    /**
     * @throws Exception
     */
    private function createInvoice(OrderIdentifier $orderIdentifier): Invoice
    {
        $issuedAt = new DateTimeImmutable('2024-01-01');

        return new Invoice(
            new InvoiceIdentifier(StrTestHelper::generateUuid()),
            $orderIdentifier,
            new UserIdentifier(StrTestHelper::generateUuid()),
            [new InvoiceLine('Pro plan', new Money(500, Currency::JPY), 2)],
            new Money(1000, Currency::JPY),
            new Money(100, Currency::JPY),
            new Money(90, Currency::JPY),
            new Money(990, Currency::JPY),
            $issuedAt,
            $issuedAt->modify('+ 10 days'),
            InvoiceStatus::ISSUED,
        );
    }

    private function createCapturedPayment(
        OrderIdentifier $orderIdentifier,
        Money $money,
        ?PaymentStatus $status = null
    ): Payment {
        return new Payment(
            new PaymentIdentifier(StrTestHelper::generateUuid()),
            $orderIdentifier,
            $money,
            new PaymentMethod(
                new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
                PaymentMethodType::CARD,
                'VISA',
                true
            ),
            new DateTimeImmutable('2023-12-24'),
            $status ?? PaymentStatus::CAPTURED,
            new DateTimeImmutable('2023-12-28'),
            new DateTimeImmutable('2023-12-31'),
            null,
            null,
            new Money(0, Currency::JPY),
            null,
        );
    }
}
