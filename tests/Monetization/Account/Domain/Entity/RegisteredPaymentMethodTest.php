<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\Entity\RegisteredPaymentMethod;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodId;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodStatus;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodType;
use Source\Monetization\Account\Domain\ValueObject\RegisteredPaymentMethodIdentifier;
use Tests\Helper\StrTestHelper;

class RegisteredPaymentMethodTest extends TestCase
{
    private function createPaymentMethod(
        ?PaymentMethodMeta $meta = null,
        bool $isDefault = false,
        PaymentMethodStatus $status = PaymentMethodStatus::ACTIVE,
    ): RegisteredPaymentMethod {
        return new RegisteredPaymentMethod(
            new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid()),
            new MonetizationAccountIdentifier(StrTestHelper::generateUuid()),
            new PaymentMethodId('pm_1234567890abcdef'),
            PaymentMethodType::CARD,
            $meta,
            $isDefault,
            $status,
        );
    }

    /**
     * 正常系: 全フィールドを指定してインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $paymentMethodIdentifier = new RegisteredPaymentMethodIdentifier(StrTestHelper::generateUuid());
        $monetizationAccountIdentifier = new MonetizationAccountIdentifier(StrTestHelper::generateUuid());
        $paymentMethodId = new PaymentMethodId('pm_1234567890abcdef');
        $meta = new PaymentMethodMeta('visa', '4242', 12, 2026);

        $paymentMethod = new RegisteredPaymentMethod(
            $paymentMethodIdentifier,
            $monetizationAccountIdentifier,
            $paymentMethodId,
            PaymentMethodType::CARD,
            $meta,
            true,
            PaymentMethodStatus::ACTIVE,
        );

        $this->assertSame($paymentMethodIdentifier, $paymentMethod->paymentMethodIdentifier());
        $this->assertSame($monetizationAccountIdentifier, $paymentMethod->monetizationAccountIdentifier());
        $this->assertSame($paymentMethodId, $paymentMethod->paymentMethodId());
        $this->assertSame(PaymentMethodType::CARD, $paymentMethod->type());
        $this->assertSame($meta, $paymentMethod->meta());
        $this->assertTrue($paymentMethod->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $paymentMethod->status());
    }

    /**
     * 正常系: デフォルト引数でインスタンスが生成されること
     */
    public function test__constructWithDefaults(): void
    {
        $paymentMethod = $this->createPaymentMethod();

        $this->assertNull($paymentMethod->meta());
        $this->assertFalse($paymentMethod->isDefault());
        $this->assertSame(PaymentMethodStatus::ACTIVE, $paymentMethod->status());
    }

    /**
     * 正常系: updateMetaでメタ情報が更新されること
     */
    public function testUpdateMeta(): void
    {
        $paymentMethod = $this->createPaymentMethod();

        $this->assertNull($paymentMethod->meta());

        $meta = new PaymentMethodMeta('visa', '4242', 12, 2026);
        $paymentMethod->updateMeta($meta);

        $this->assertSame($meta, $paymentMethod->meta());
    }

    /**
     * 正常系: markAsDefaultでデフォルトに設定されること
     */
    public function testMarkAsDefault(): void
    {
        $paymentMethod = $this->createPaymentMethod();

        $this->assertFalse($paymentMethod->isDefault());

        $paymentMethod->markAsDefault();

        $this->assertTrue($paymentMethod->isDefault());
    }

    /**
     * 正常系: unmarkAsDefaultでデフォルトが解除されること
     */
    public function testUnmarkAsDefault(): void
    {
        $paymentMethod = $this->createPaymentMethod(isDefault: true);

        $this->assertTrue($paymentMethod->isDefault());

        $paymentMethod->unmarkAsDefault();

        $this->assertFalse($paymentMethod->isDefault());
    }

    /**
     * 正常系: deactivateでステータスがINACTIVEに変更されること
     */
    public function testDeactivate(): void
    {
        $paymentMethod = $this->createPaymentMethod();

        $this->assertSame(PaymentMethodStatus::ACTIVE, $paymentMethod->status());

        $paymentMethod->deactivate();

        $this->assertSame(PaymentMethodStatus::INACTIVE, $paymentMethod->status());
    }

    /**
     * 正常系: activateでステータスがACTIVEに変更されること
     */
    public function testActivate(): void
    {
        $paymentMethod = $this->createPaymentMethod(status: PaymentMethodStatus::INACTIVE);

        $this->assertSame(PaymentMethodStatus::INACTIVE, $paymentMethod->status());

        $paymentMethod->activate();

        $this->assertSame(PaymentMethodStatus::ACTIVE, $paymentMethod->status());
    }
}
