<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\ExternalPaymentMethodId;

class ExternalPaymentMethodIdTest extends TestCase
{
    /**
     * 正常系: 有効なPayment Method IDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = 'pm_1234567890abcdef';
        $externalPaymentMethodId = new ExternalPaymentMethodId($id);
        $this->assertSame($id, (string) $externalPaymentMethodId);
    }

    /**
     * 異常系: pm_で始まらない場合、例外が発生すること
     */
    public function testValidateInvalidPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExternalPaymentMethodId('invalid_1234567890');
    }

    /**
     * 異常系: 長さが短すぎる場合、例外が発生すること
     */
    public function testValidateTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExternalPaymentMethodId('pm_123');
    }
}
