<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;

class InvalidAuthorizationRequestExceptionTest extends TestCase
{
    public function testItPreservesCauseWithASafeMessage(): void
    {
        $previous = new RuntimeException('secret');
        $exception = new InvalidAuthorizationRequestException($previous);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame('Invalid authorization request.', $exception->getMessage());
        $this->assertNull((new InvalidAuthorizationRequestException())->getPrevious());
    }
}
