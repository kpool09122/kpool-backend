<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\PaymentMethodArchiveIdentifier;
use Tests\Helper\StrTestHelper;

class PaymentMethodArchiveIdentifierTest extends TestCase
{
    public function testAcceptsIdentifier(): void
    {
        $value = StrTestHelper::generateUuid();
        self::assertSame($value, (string) new PaymentMethodArchiveIdentifier($value));
    }

    public function testRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PaymentMethodArchiveIdentifier('not-a-uuid');
    }
}
