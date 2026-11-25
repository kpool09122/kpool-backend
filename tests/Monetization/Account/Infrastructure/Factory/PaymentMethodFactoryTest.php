<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Infrastructure\Factory;

use Illuminate\Contracts\Container\BindingResolutionException;
use Source\Monetization\Account\Domain\Factory\PaymentMethodFactoryInterface;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Infrastructure\Factory\PaymentMethodFactory;
use Source\Shared\Application\Service\Uuid\UuidValidator;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class PaymentMethodFactoryTest extends TestCase
{
    /**
     * 正常系: DIが正しく動作すること.
     *
     * @throws BindingResolutionException
     */
    public function test__construct(): void
    {
        $factory = $this->app()->make(PaymentMethodFactoryInterface::class);
        $this->assertInstanceOf(PaymentMethodFactory::class, $factory);
    }

    /**
     * 正常系: 正しくPaymentMethodエンティティが作成できること.
     *
     * @throws BindingResolutionException
     */
    public function testCreate(): void
    {
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_' . StrTestHelper::generateStr(10));
        $type = PaymentMethodType::CARD;

        $factory = $this->app()->make(PaymentMethodFactoryInterface::class);
        $paymentMethod = $factory->create(
            $monetizationAccountIdentifier,
            $externalPaymentMethodId,
            $type,
        );

        $this->assertTrue(UuidValidator::isValid((string) $paymentMethod->paymentMethodIdentifier()));
        $this->assertSame($monetizationAccountIdentifier, $paymentMethod->monetizationAccountIdentifier());
        $this->assertSame($externalPaymentMethodId, $paymentMethod->externalPaymentMethodId());
        $this->assertSame($type, $paymentMethod->type());
        $this->assertNull($paymentMethod->meta());
        $this->assertFalse($paymentMethod->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $paymentMethod->status());
    }
}
