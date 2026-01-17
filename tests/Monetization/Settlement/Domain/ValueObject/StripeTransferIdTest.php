<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\StripeTransferId;

class StripeTransferIdTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンス化できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = 'tr_1234567890abcdefghijklmn';

        $stripeTransferId = new StripeTransferId($id);

        $this->assertSame($id, (string) $stripeTransferId);
    }

    /**
     * 異常系: tr_で始まらない場合、例外が発生すること
     *
     * @return void
     */
    public function testValidateWhenNotStartsWithTr(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Stripe Transfer ID format.');

        new StripeTransferId('invalid_1234567890');
    }

    /**
     * 異常系: 短すぎる場合、例外が発生すること
     *
     * @return void
     */
    public function testValidateWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Stripe Transfer ID format.');

        new StripeTransferId('tr_123');
    }
}
