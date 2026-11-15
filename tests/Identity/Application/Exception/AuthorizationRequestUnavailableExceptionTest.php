<?php

declare(strict_types=1);

namespace Tests\Identity\Application\Exception;

use RuntimeException;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Tests\TestCase;

class AuthorizationRequestUnavailableExceptionTest extends TestCase
{
    public function testRetainsReasonAndPrevious(): void
    {
        $previous = new RuntimeException('failure');
        $exception = new AuthorizationRequestUnavailableException('expired', $previous);
        $this->assertSame('expired', $exception->reason);
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
