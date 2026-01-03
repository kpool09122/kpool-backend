<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\StripeConnectedAccountId;

class StripeConnectedAccountIdTest extends TestCase
{
    /**
     * 正常系: 有効なStripe Connected Account IDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = 'acct_1234567890abcdef';
        $stripeConnectedAccountId = new StripeConnectedAccountId($id);
        $this->assertSame($id, (string) $stripeConnectedAccountId);
    }

    /**
     * 異常系: acct_で始まらない場合、例外が発生すること
     */
    public function testValidateInvalidPrefix(): void
    {
        $id = 'invalid_1234567890';
        $this->expectException(InvalidArgumentException::class);
        new StripeConnectedAccountId($id);
    }

    /**
     * 異常系: 長さが短すぎる場合、例外が発生すること
     */
    public function testValidateTooShort(): void
    {
        $id = 'acct_123';
        $this->expectException(InvalidArgumentException::class);
        new StripeConnectedAccountId($id);
    }
}
