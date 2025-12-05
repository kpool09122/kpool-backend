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
        $invoice = $this->createInvoice();
        $payment = $this->createCapturedPayment($invoice->total());
        $paidAt = new DateTimeImmutable('+1 minute');

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);
        $matcher->match($invoice, $payment, $paidAt);

        $this->assertSame(InvoiceStatus::PAID, $invoice->status());
        $this->assertSame($paidAt, $invoice->paidAt());
    }

    /**
     * 異常系: 支払いステータスが確定済みでない場合、例外がスローされること.
     *
     * @return void
     * @throws Exception
     */
    public function testRejectsWhenPaymentStatusIsNotCaptured(): void
    {
        $invoice = $this->createInvoice();
        $payment = $this->createCapturedPayment($invoice->total(), PaymentStatus::AUTHORIZED);

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
        $invoice = $this->createInvoice();
        $payment = $this->createCapturedPayment(new Money($invoice->total()->amount() - 10, Currency::JPY));

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
        $invoice = $this->createInvoice();
        $payment = $this->createCapturedPayment(new Money($invoice->total()->amount(), Currency::KRW));

        $matcher = $this->app->make(PaymentMatcherServiceInterface::class);

        $this->expectException(DomainException::class);
        $matcher->match($invoice, $payment, new DateTimeImmutable());
    }

    /**
     * @return Invoice
     * @throws Exception
     */
    private function createInvoice(): Invoice
    {
        $issuedAt = new DateTimeImmutable('2024-01-01');

        return new Invoice(
            new InvoiceIdentifier(StrTestHelper::generateUlid()),
            new UserIdentifier(StrTestHelper::generateUlid()),
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
        Money $money,
        ?PaymentStatus $status = null
    ): Payment {
        return new Payment(
            new PaymentIdentifier(StrTestHelper::generateUlid()),
            $money,
            new PaymentMethod(
                new PaymentMethodIdentifier(StrTestHelper::generateUlid()),
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
