<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Source\Identity\Application\Exception\InvalidAuthorizationTokenException;

class InvalidAuthorizationTokenExceptionTest extends TestCase
{
    public function testItPreservesPreviousExceptionWithoutExposingItsMessage(): void
    {
        $previous = new RuntimeException('secret');
        $exception = new InvalidAuthorizationTokenException($previous);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame('Invalid authorization token.', $exception->getMessage());
        $this->assertNull((new InvalidAuthorizationTokenException())->getPrevious());
    }
}
