<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\RefundPayment;

use DateTimeImmutable;
use DateTimeInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Application\UseCase\Command\RefundPayment\RefundPaymentOutput;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RefundPaymentOutputTest extends TestCase
{
    /**
     * 正常系: Paymentがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    public function testToArrayWithPayment(): void
    {
        $paymentId = new PaymentIdentifier(StrTestHelper::generateUuid());
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);
        $refundedMoney = new Money(500, Currency::JPY);
        $paymentMethodIdentifier = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        $now = new DateTimeImmutable();

        $payment = new Payment(
            $paymentId,
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $paymentMethodIdentifier,
            $now,
            PaymentStatus::PARTIALLY_REFUNDED,
            $now,
            $now,
            null,
            null,
            $refundedMoney,
            $now,
            'customer request',
            stripePaymentIntentId: new StripePaymentIntentId('pi_test123456'),
        );

        $output = new RefundPaymentOutput();
        $output->setPayment($payment);

        $result = $output->toArray();

        $this->assertArrayNotHasKey('paymentMethodType', $result);
        $this->assertArrayNotHasKey('paymentMethodBrand', $result);
        $this->assertArrayNotHasKey('paymentMethodLast4', $result);

        $this->assertSame((string) $paymentId, $result['paymentId']);
        $this->assertSame((string) $orderIdentifier, $result['orderIdentifier']);
        $this->assertSame((string) $buyerMonetizationAccountIdentifier, $result['buyerMonetizationAccountIdentifier']);
        $this->assertSame(1000, $result['amount']);
        $this->assertSame('JPY', $result['currency']);
        $this->assertSame((string) $paymentMethodIdentifier, $result['paymentMethodIdentifier']);
        $this->assertSame('partially_refunded', $result['status']);
        $this->assertSame($now->format(DateTimeInterface::ATOM), $result['createdAt']);
        $this->assertSame($now->format(DateTimeInterface::ATOM), $result['authorizedAt']);
        $this->assertSame($now->format(DateTimeInterface::ATOM), $result['capturedAt']);
        $this->assertNull($result['failedAt']);
        $this->assertNull($result['failureReason']);
        $this->assertSame(500, $result['refundedAmount']);
        $this->assertSame('JPY', $result['refundedCurrency']);
        $this->assertSame($now->format(DateTimeInterface::ATOM), $result['lastRefundedAt']);
        $this->assertSame('customer request', $result['lastRefundReason']);
    }

    /**
     * 正常系: Paymentがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutPayment(): void
    {
        $output = new RefundPaymentOutput();

        $result = $output->toArray();

        $this->assertSame([
            'paymentId' => null,
            'orderIdentifier' => null,
            'buyerMonetizationAccountIdentifier' => null,
            'amount' => null,
            'currency' => null,
            'paymentMethodIdentifier' => null,
            'status' => null,
            'createdAt' => null,
            'authorizedAt' => null,
            'capturedAt' => null,
            'failedAt' => null,
            'failureReason' => null,
            'refundedAmount' => null,
            'refundedCurrency' => null,
            'lastRefundedAt' => null,
            'lastRefundReason' => null,
        ], $result);
    }
}
