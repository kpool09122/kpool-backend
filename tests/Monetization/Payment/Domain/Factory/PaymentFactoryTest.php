<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\Factory;

use DateTimeImmutable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Payment\Domain\Factory\PaymentFactory;
use Source\Monetization\Payment\Domain\Factory\PaymentFactoryInterface;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Payment\Domain\ValueObject\PaymentStatus;
use Source\Shared\Application\Service\Ulid\UlidValidator;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;
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
        $money = new Money(100, Currency::KRW);
        $method = new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUlid()),
            PaymentMethodType::CARD,
            'VISA **** 4242',
            true,
        );
        $createdAt = new DateTimeImmutable();
        $factory = $this->app->make(PaymentFactoryInterface::class);
        $payment = $factory->create(
            $money,
            $method,
            $createdAt,
        );

        $this->assertTrue(UlidValidator::isValid((string)$payment->paymentId()));
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
