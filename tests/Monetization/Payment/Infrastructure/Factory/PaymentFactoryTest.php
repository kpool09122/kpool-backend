<?php

declare(strict_types=1);

namespace Monetization\Payment\Infrastructure\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Monetization\Payment\Infrastructure\Factory\PaymentFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
use Source\Shared\Domain\ValueObject\OrderIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(PaymentFactoryInterface::class);
        $this->assertInstanceOf(PaymentFactory::class, $factory);
    }

    /**
     * 正常系: Payment Entityが正しく作成されること.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $orderIdentifier = new OrderIdentifier(StrTestHelper::generateUuid());
        $buyerMonetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $money = new Money(100, Currency::KRW);
        $method = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUuid()),
            PaymentMethodType::CARD,
            'VISA **** 4242',
            true,
        );
        $createdAt = new DateTimeImmutable();
        $factory = $this->app->make(PaymentFactoryInterface::class);
        $payment = $factory->create(
            $orderIdentifier,
            $buyerMonetizationAccountIdentifier,
            $money,
            $method,
            $createdAt,
        );

        $this->assertTrue(UuidValidator::isValid((string)$payment->paymentId()));
        $this->assertSame($orderIdentifier, $payment->orderIdentifier());
        $this->assertSame($buyerMonetizationAccountIdentifier, $payment->buyerMonetizationAccountIdentifier());
        $this->assertSame($money, $payment->money());
        $this->assertSame($method, $payment->paymentMethod());
        $this->assertSame($createdAt, $payment->createdAt());
        $this->assertSame(PaymentStatus::PENDING, $payment->status());
        $this->assertNull($payment->authorizedAt());
        $this->assertNull($payment->capturedAt());
        $this->assertNull($payment->failedAt());
        $this->assertNull($payment->failureReason());
        $this->assertSame(0, $payment->refundedMoney()->amount());
        $this->assertSame($money->currency(), $payment->refundedMoney()->currency());
        $this->assertNull($payment->lastRefundedAt());
    }
}
