<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;

class AuthorizationAccountUnavailableExceptionTest extends TestCase
{
    public function testRetainsTheOriginalError(): void
    {
        $previous = new RuntimeException('Account lookup failed.');
        $exception = new AuthorizationAccountUnavailableException($previous);

        self::assertSame('Original account is unavailable.', $exception->getMessage());
        self::assertSame($previous, $exception->getPrevious());
    }
}
