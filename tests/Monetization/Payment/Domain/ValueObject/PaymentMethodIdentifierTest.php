<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\PaymentMethodIdentifier;
use Tests\Helper\StrTestHelper;

class PaymentMethodIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $paymentMethodIdentifier = new PaymentMethodIdentifier($ulid);
        $this->assertSame($ulid, (string)$paymentMethodIdentifier);
    }

    /**
     * 異常系: ulidが不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $ulid = 'invalid-ulid';
        $this->expectException(InvalidArgumentException::class);
        new PaymentMethodIdentifier($ulid);
    }
}
