<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\StripePaymentIntentId;

class StripePaymentIntentIdTest extends TestCase
{
    /**
     * 正常系: 有効なStripe Payment Intent IDでインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = 'pi_1234567890';
        $stripePaymentIntentId = new StripePaymentIntentId($id);
        $this->assertSame($id, (string)$stripePaymentIntentId);
    }

    /**
     * 異常系: pi_で始まらない場合、例外が発生すること
     *
     * @return void
     */
    public function testValidateWithInvalidPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Stripe Payment Intent ID format.');
        new StripePaymentIntentId('invalid_1234567890');
    }

    /**
     * 異常系: 10文字未満の場合、例外が発生すること
     *
     * @return void
     */
    public function testValidateWithTooShortId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Stripe Payment Intent ID format.');
        new StripePaymentIntentId('pi_12345');
    }
}
