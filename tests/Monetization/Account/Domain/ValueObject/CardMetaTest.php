<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\CardMeta;

class CardMetaTest extends TestCase
{
    /**
     * 正常系: 全フィールドを指定してインスタンスが作成できること
     */
    public function test__construct(): void
    {
        $cardMeta = new CardMeta(
            'visa',
            '4242',
            12,
            2030,
            'fp_abc123',
        );

        $this->assertSame('visa', $cardMeta->brand());
        $this->assertSame('4242', $cardMeta->last4());
        $this->assertSame(12, $cardMeta->expMonth());
        $this->assertSame(2030, $cardMeta->expYear());
        $this->assertSame('fp_abc123', $cardMeta->fingerprint());
    }

    /**
     * 正常系: 全フィールドがnullでもインスタンスが作成できること
     */
    public function test__constructWithAllNull(): void
    {
        $cardMeta = new CardMeta();

        $this->assertNull($cardMeta->brand());
        $this->assertNull($cardMeta->last4());
        $this->assertNull($cardMeta->expMonth());
        $this->assertNull($cardMeta->expYear());
        $this->assertNull($cardMeta->fingerprint());
    }

    /**
     * 正常系: 一部フィールドのみ指定してインスタンスが作成できること
     */
    public function test__constructWithPartialFields(): void
    {
        $cardMeta = new CardMeta(
            'mastercard',
            '1234',
        );

        $this->assertSame('mastercard', $cardMeta->brand());
        $this->assertSame('1234', $cardMeta->last4());
        $this->assertNull($cardMeta->expMonth());
        $this->assertNull($cardMeta->expYear());
        $this->assertNull($cardMeta->fingerprint());
    }
}
