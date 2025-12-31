<?php

declare(strict_types=1);

namespace Tests\Monetization\Payment\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Payment\Domain\ValueObject\PaymentIdentifier;
use Tests\Helper\StrTestHelper;

class PaymentIdTest extends TestCase
{
    /**
     * 正常系: Ulid 形式の ID を受け付けること.
     */
    public function testAcceptsUlid(): void
    {
        $paymentId = new PaymentIdentifier(StrTestHelper::generateUuid());

        $this->assertNotEmpty((string)$paymentId);
    }

    /**
     * 異常系: 不正な形式は受け付けないこと.
     */
    public function testRejectsInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PaymentIdentifier('not-an-ulid');
    }
}
