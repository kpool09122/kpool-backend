<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodMeta;

class PaymentMethodMetaTest extends TestCase
{
    /**
     * 正常系: 全フィールドを指定してインスタンスが作成できること
     */
    public function test__construct(): void
    {
        $meta = new PaymentMethodMeta(
            'visa',
            '4242',
            12,
            2026,
        );

        $this->assertSame('visa', $meta->brand());
        $this->assertSame('4242', $meta->last4());
        $this->assertSame(12, $meta->expMonth());
        $this->assertSame(2026, $meta->expYear());
    }

    /**
     * 正常系: 全フィールドがnullでもインスタンスが作成できること
     */
    public function test__constructWithAllNull(): void
    {
        $meta = new PaymentMethodMeta();

        $this->assertNull($meta->brand());
        $this->assertNull($meta->last4());
        $this->assertNull($meta->expMonth());
        $this->assertNull($meta->expYear());
    }

    /**
     * 正常系: 一部フィールドのみ指定してインスタンスが作成できること
     */
    public function test__constructWithPartialFields(): void
    {
        $meta = new PaymentMethodMeta(
            'mastercard',
            '5678',
        );

        $this->assertSame('mastercard', $meta->brand());
        $this->assertSame('5678', $meta->last4());
        $this->assertNull($meta->expMonth());
        $this->assertNull($meta->expYear());
    }
}
