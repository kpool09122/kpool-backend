<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Tests\Helper\StrTestHelper;

class PaymentIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $paymentIdentifier = new PaymentIdentifier($id);
        $this->assertSame($id, (string)$paymentIdentifier);
    }

    /**
     * 異常系: 値が不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new PaymentIdentifier($id);
    }
}
