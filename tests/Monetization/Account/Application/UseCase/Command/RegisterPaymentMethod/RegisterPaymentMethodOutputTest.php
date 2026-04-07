<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod;

use Source\Monetization\Account\Application\UseCase\Command\RegisterPaymentMethod\RegisterPaymentMethodOutput;
use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;
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
        $identifier = new RegisteredPaymentMethodIdentifier($identifierValue);
        $accountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_1234567890');
        $meta = new PaymentMethodMeta(
            brand: 'visa',
            last4: '4242',
            expMonth: 12,
            expYear: 2030,
        );

        $paymentMethod = new RegisteredPaymentMethod(
            paymentMethodIdentifier: $identifier,
            monetizationAccountIdentifier: $accountIdentifier,
            paymentMethodId: $paymentMethodId,
            type: PaymentMethodType::CARD,
            meta: $meta,
            isDefault: true,
        );

        $output = new RegisterPaymentMethodOutput();
        $output->setRegisteredPaymentMethod($paymentMethod);

        $result = $output->toArray();

        $this->assertSame($identifierValue, $result['registeredPaymentMethodIdentifier']);
        $this->assertSame('pm_1234567890', $result['paymentMethodId']);
        $this->assertSame('card', $result['type']);
        $this->assertSame('visa', $result['brand']);
        $this->assertSame('4242', $result['last4']);
        $this->assertSame(12, $result['expMonth']);
        $this->assertSame(2030, $result['expYear']);
        $this->assertTrue($result['isDefault']);
        $this->assertFalse($result['skipped']);
    }

    /**
     * 正常系: 支払い手段がセットされていてメタ情報がない場合、toArrayがnullのメタ情報を返すこと
     */
    public function testToArrayWithPaymentMethodWithoutMeta(): void
    {
        $identifierValue = StrTestHelper::generateUuid();
        $identifier = new RegisteredPaymentMethodIdentifier($identifierValue);
        $accountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_1234567890');

        $paymentMethod = new RegisteredPaymentMethod(
            paymentMethodIdentifier: $identifier,
            monetizationAccountIdentifier: $accountIdentifier,
            paymentMethodId: $paymentMethodId,
            type: PaymentMethodType::CARD,
        );

        $output = new RegisterPaymentMethodOutput();
        $output->setRegisteredPaymentMethod($paymentMethod);

        $result = $output->toArray();

        $this->assertSame($identifierValue, $result['registeredPaymentMethodIdentifier']);
        $this->assertNull($result['brand']);
        $this->assertNull($result['last4']);
        $this->assertNull($result['expMonth']);
        $this->assertNull($result['expYear']);
        $this->assertFalse($result['isDefault']);
        $this->assertFalse($result['skipped']);
    }

    /**
     * 正常系: 支払い手段がセットされていない場合、toArrayがnullの配列を返すこと
     */
    public function testToArrayWithoutPaymentMethod(): void
    {
        $output = new RegisterPaymentMethodOutput();

        $result = $output->toArray();

        $this->assertSame([
            'registeredPaymentMethodIdentifier' => null,
            'skipped' => false,
        ], $result);
    }

    /**
     * 正常系: skippedがtrueにセットされている場合、toArrayが正しい値を返すこと
     */
    public function testToArrayWithSkipped(): void
    {
        $output = new RegisterPaymentMethodOutput();
        $output->setSkipped(true);

        $result = $output->toArray();

        $this->assertSame([
            'registeredPaymentMethodIdentifier' => null,
            'skipped' => true,
        ], $result);
    }
}
