<?php

declare(strict_types=1);

namespace Tests\Account\AccountVerification\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\AccountVerification\Domain\ValueObject\VerificationIdentifier;
use Tests\Helper\StrTestHelper;

class VerificationIdentifierTest extends TestCase
{
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $identifier = new VerificationIdentifier($uuid);

        $this->assertSame($uuid, (string) $identifier);
    }

    public function testThrowsExceptionWhenInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VerificationIdentifier('invalid-uuid');
    }
}
