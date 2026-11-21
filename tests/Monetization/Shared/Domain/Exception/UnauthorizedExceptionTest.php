<?php

declare(strict_types=1);

namespace Tests\Monetization\Shared\Domain\Exception;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;

class UnauthorizedExceptionTest extends TestCase
{
    public function testPreservesPreviousExceptionAndZeroCode(): void
    {
        $previous = new RuntimeException('previous');
        $exception = new UnauthorizedException($previous);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(0, $exception->getCode());
        self::assertSame('Monetization operation is not authorized.', $exception->getMessage());
        self::assertNull((new UnauthorizedException())->getPrevious());
    }
}
