<?php

declare(strict_types=1);

namespace Tests\Account\PrincipalGroup\Domain\ValueObject;

use InvalidArgumentException;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleIdentifierTest extends TestCase
{
    public function test__construct(): void
    {
        $uuid = StrTestHelper::generateUuid();
        $roleIdentifier = new RoleIdentifier($uuid);

        $this->assertSame($uuid, (string) $roleIdentifier);
    }

    public function testThrowsExceptionWhenInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid RoleIdentifier.');

        new RoleIdentifier('invalid');
    }
}
