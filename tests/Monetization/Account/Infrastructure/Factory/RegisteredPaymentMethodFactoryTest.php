<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\Factory\RegisteredPaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Infrastructure\Factory\RegisteredPaymentMethodFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RegisteredPaymentMethodFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app->make(RegisteredPaymentMethodFactoryInterface::class);
        $this->assertInstanceOf(RegisteredPaymentMethodFactory::class, $factory);
    }

    /**
     * 正常系: 正しくRegisteredPaymentMethodエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_' . StrTestHelper::generateStr(10));
        $type = PaymentMethodType::CARD;

        $factory = $this->app->make(RegisteredPaymentMethodFactoryInterface::class);
        $registeredPaymentMethod = $factory->create(
            $monetizationAccountIdentifier,
            $paymentMethodId,
            $type,
        );

        $this->assertTrue(UuidValidator::isValid((string) $registeredPaymentMethod->paymentMethodIdentifier()));
        $this->assertSame($monetizationAccountIdentifier, $registeredPaymentMethod->monetizationAccountIdentifier());
        $this->assertSame($paymentMethodId, $registeredPaymentMethod->paymentMethodId());
        $this->assertSame($type, $registeredPaymentMethod->type());
        $this->assertNull($registeredPaymentMethod->meta());
        $this->assertFalse($registeredPaymentMethod->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $registeredPaymentMethod->status());
    }
}
