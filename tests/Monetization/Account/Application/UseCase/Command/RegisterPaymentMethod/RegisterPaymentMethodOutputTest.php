<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\Entity\PaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RegisterPaymentMethodOutputTest extends TestCase
{
    /**
     * 正常系: 支払い手段がセットされている場合、toArrayが正しい値を返すこと
     */
    public function testToArrayWithPaymentMethod(): void
    {
        $identifierValue = StrTestHelper::generateUuid();
        $identifier = new PaymentMethodIdentifier($identifierValue);
        $accountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_1234567890');
        $meta = new PaymentMethodMeta(
            brand: 'visa',
            last4: '4242',
            expMonth: 12,
            expYear: 2030,
        );

        $paymentMethod = new PaymentMethod(
            paymentMethodIdentifier: $identifier,
            monetizationAccountIdentifier: $accountIdentifier,
            externalPaymentMethodId: $externalPaymentMethodId,
            type: PaymentMethodType::CARD,
            meta: $meta,
            isDefault: true,
        );

        $output = new RegisterPaymentMethodOutput();
        $output->setPaymentMethod($paymentMethod);

        $result = $output->toArray();

        $this->assertSame($identifierValue, $result['paymentMethodIdentifier']);
        $this->assertSame('pm_1234567890', $result['externalPaymentMethodId']);
        $this->assertSame('card', $result['type']);
        $this->assertSame('visa', $result['brand']);
        $this->assertSame('4242', $result['last4']);
        $this->assertSame(12, $result['expMonth']);
        $this->assertSame(2030, $result['expYear']);
        $this->assertTrue($result['isDefault']);
    }

    /**
     * 正常系: 支払い手段がセットされていてメタ情報がない場合、toArrayがnullのメタ情報を返すこと
     */
    public function testToArrayWithPaymentMethodWithoutMeta(): void
    {
        $identifierValue = StrTestHelper::generateUuid();
        $identifier = new PaymentMethodIdentifier($identifierValue);
        $accountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $externalPaymentMethodId = new ExternalPaymentMethodId('pm_1234567890');

        $paymentMethod = new PaymentMethod(
            paymentMethodIdentifier: $identifier,
            monetizationAccountIdentifier: $accountIdentifier,
            externalPaymentMethodId: $externalPaymentMethodId,
            type: PaymentMethodType::CARD,
        );

        $output = new RegisterPaymentMethodOutput();
        $output->setPaymentMethod($paymentMethod);

        $result = $output->toArray();

        $this->assertSame($identifierValue, $result['paymentMethodIdentifier']);
        $this->assertNull($result['brand']);
        $this->assertNull($result['last4']);
        $this->assertNull($result['expMonth']);
        $this->assertNull($result['expYear']);
        $this->assertFalse($result['isDefault']);
    }

    /**
     * 正常系: 支払い手段がセットされていない場合、toArrayがnullの配列を返すこと
     */
    public function testToArrayWithoutPaymentMethod(): void
    {
        $output = new RegisterPaymentMethodOutput();

        $result = $output->toArray();

        $this->assertSame([
            'paymentMethodIdentifier' => null,
        ], $result);
    }
}
