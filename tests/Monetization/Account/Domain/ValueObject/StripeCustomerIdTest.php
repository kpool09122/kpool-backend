<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\StripeCustomerId;

class StripeCustomerIdTest extends TestCase
{
    /**
     * 正常系: 有効なStripe Customer IDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = 'cus_1234567890abcdef';
        $stripeCustomerId = new StripeCustomerId($id);
        $this->assertSame($id, (string) $stripeCustomerId);
    }

    /**
     * 異常系: cus_で始まらない場合、例外が発生すること
     */
    public function testValidateInvalidPrefix(): void
    {
        $id = 'invalid_1234567890';
        $this->expectException(InvalidArgumentException::class);
        new StripeCustomerId($id);
    }

    /**
     * 異常系: 長さが短すぎる場合、例外が発生すること
     */
    public function testValidateTooShort(): void
    {
        $id = 'cus_123';
        $this->expectException(InvalidArgumentException::class);
        new StripeCustomerId($id);
    }
}
