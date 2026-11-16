<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Source\Identity\Application\Exception\AuthorizationConnectionNotFoundException;

class AuthorizationConnectionNotFoundExceptionTest extends TestCase
{
    public function testPreservesPreviousAndUsesZeroCode(): void
    {
        $previous = new RuntimeException('cause');
        $exception = new AuthorizationConnectionNotFoundException($previous);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(0, $exception->getCode());
        self::assertSame('Authorization connection not found.', $exception->getMessage());
        self::assertNull((new AuthorizationConnectionNotFoundException())->getPrevious());
    }
}
