<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethod;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodType;
use Tests\Helper\StrTestHelper;

class PaymentMethodTest extends TestCase
{
    /**
     * 正常系: 支払い方法の情報を保持できること.
     */
    public function test__construct(): void
    {
        $paymentMethodIdentifier = new PaymentMethodIdentifier(StrTestHelper::generateUlid());
        $type = PaymentMethodType::CARD;
        $label = 'VISA **** 4242';
        $method = new PaymentMethod(
            $paymentMethodIdentifier,
            $type,
            $label,
            true
        );

        $this->assertSame($paymentMethodIdentifier, $method->paymentMethodIdentifier());
        $this->assertSame($type, $method->type());
        $this->assertSame($label, $method->label());
        $this->assertTrue($method->isRecurringEnabled());
    }

    /**
     * 異常系: ラベルが空の場合は例外となること.
     */
    public function testRejectsEmptyLabel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PaymentMethod(
            new PaymentMethodIdentifier(StrTestHelper::generateUlid()),
            PaymentMethodType::CARD,
            '   ',
            true
        );
    }
}
