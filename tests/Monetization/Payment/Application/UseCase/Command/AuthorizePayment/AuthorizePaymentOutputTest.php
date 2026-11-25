<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Application\UseCase\Command\AuthorizePayment;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Application\UseCase\Command\AuthorizePayment\AuthorizePaymentOutput;
use Source\Monetization\Payment\Domain\Entity\Payment;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AuthorizePaymentOutputTest extends TestCase
{
    /** @return array<string, array{?PaymentMethodMeta, ?string, ?string}> */
    public static function paymentMethodMetadataProvider(): array
    {
        return [
            'card metadata' => [new PaymentMethodMeta('visa', '1234', 12, 2030), 'visa', '1234'],
            'missing metadata' => [null, null, null],
            'partial metadata' => [new PaymentMethodMeta(last4: '1234'), null, '1234'],
        ];
    }

    /**
     * 正常系: Paymentがセットされている場合、toArrayが正しい値を返すこと.
     *
     * @return void
     */
    #[DataProvider('paymentMethodMetadataProvider')]
    public function testToArrayWithPayment(?PaymentMethodMeta $meta, ?string $expectedBrand, ?string $expectedLast4): void
    {
        $paymentId = new PaymentIdentifier(StrTestHelper::generateUuid());
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money = new Money(1000, Currency::JPY);
        $paymentMethodIdentifier = new PaymentMethodIdentifier(StrTestHelper::generateUuid());
        $now = new DateTimeImmutable();

        $payment = new Payment(
            $paymentId,
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $paymentMethodIdentifier,
            $now,
            PaymentStatus::AUTHORIZED,
            $now,
            null,
            null,
            null,
            new Money(0, Currency::JPY),
            null,
            stripePaymentIntentId: new StripePaymentIntentId('pi_test123456'),
        );

        $output = new AuthorizePaymentOutput();
        $output->setPayment($payment);
        $output->setPaymentMethod(new PaymentMethod($paymentMethodIdentifier, $buyerMonetizationAccountIdentifier, new ExternalPaymentMethodId('pm_1234567890'), PaymentMethodType::CARD, meta: $meta));

        $result = $output->toArray();

        $this->assertSame((string) $paymentId, $result['paymentId']);
        $this->assertSame((string) $orderIdentifier, $result['orderIdentifier']);
        $this->assertSame((string) $buyerMonetizationAccountIdentifier, $result['buyerMonetizationAccountIdentifier']);
        $this->assertSame(1000, $result['amount']);
        $this->assertSame('JPY', $result['currency']);
        $this->assertSame((string) $paymentMethodIdentifier, $result['paymentMethodIdentifier']);
        $this->assertSame('card', $result['paymentMethodType']);
        $this->assertSame($expectedBrand, $result['paymentMethodBrand']);
        $this->assertSame($expectedLast4, $result['paymentMethodLast4']);
        $this->assertSame('authorized', $result['status']);
        $this->assertSame($now->format(DateTimeInterface::ATOM), $result['createdAt']);
        $this->assertSame($now->format(DateTimeInterface::ATOM), $result['authorizedAt']);
        $this->assertNull($result['capturedAt']);
        $this->assertNull($result['failedAt']);
        $this->assertNull($result['failureReason']);
        $this->assertSame(0, $result['refundedAmount']);
        $this->assertSame('JPY', $result['refundedCurrency']);
        $this->assertNull($result['lastRefundedAt']);
        $this->assertNull($result['lastRefundReason']);
    }

    /**
     * 正常系: Paymentがセットされていない場合、toArrayが全てnullの配列を返すこと.
     *
     * @return void
     */
    public function testToArrayWithoutPayment(): void
    {
        $output = new AuthorizePaymentOutput();

        $result = $output->toArray();

        $this->assertSame([
            'paymentId' => null,
            'orderIdentifier' => null,
            'buyerMonetizationAccountIdentifier' => null,
            'amount' => null,
            'currency' => null,
            'paymentMethodIdentifier' => null,
            'paymentMethodType' => null,
            'paymentMethodBrand' => null,
            'paymentMethodLast4' => null,
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
