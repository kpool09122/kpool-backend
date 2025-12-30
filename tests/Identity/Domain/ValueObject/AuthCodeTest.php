<?php

declare(strict_types=1);

namespace Tests\Identity\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Identity\Domain\ValueObject\AuthCode;

class AuthCodeTest extends TestCase
{
    public function test__construct(): void
    {
        $authCode = new AuthCode('123456');

        $this->assertSame('123456', (string)$authCode);
    }

    public function testThrowsExceptionWhenLengthIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuthCode('12345');
    }

    public function testThrowsExceptionWhenNotNumeric(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AuthCode('12ab56');
    }
}
