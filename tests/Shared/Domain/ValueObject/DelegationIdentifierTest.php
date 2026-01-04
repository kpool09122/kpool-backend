<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\DelegationIdentifier;
use Tests\Helper\StrTestHelper;

class DelegationIdentifierTest extends TestCase
{
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $identifier = new DelegationIdentifier($uuid);
        $this->assertSame($uuid, (string) $identifier);
    }

    public function testInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DelegationIdentifier('invalid-uuid');
    }

    public function testEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DelegationIdentifier('');
    }
}
