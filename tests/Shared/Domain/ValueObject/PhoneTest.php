<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\Phone;

class PhoneTest extends TestCase
{
    public function testPhoneNormalizesCommonSeparators(): void
    {
        $phone = new Phone('+81 (90) 1234-5678');

        $this->assertSame('+819012345678', (string) $phone);
    }

    public function testPhoneRejectsTooShortValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number must be between 7 and 15 digits.');

        new Phone('123456');
    }

    public function testPhoneRejectsInvalidCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Phone number must contain only digits with an optional leading +.');

        new Phone('+81-90-ABCD');
    }
}
