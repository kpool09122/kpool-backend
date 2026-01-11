<?php

declare(strict_types=1);

namespace Tests\Account\DelegationPermission\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\DelegationPermission\Domain\ValueObject\DelegationPermissionIdentifier;

class DelegationPermissionIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDv7でインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $uuid = '01945a3d-4c5b-7abc-8def-1234567890ab';
        $identifier = new DelegationPermissionIdentifier($uuid);
        $this->assertSame($uuid, (string) $identifier);
    }

    /**
     * 異常系：無効なUUIDが渡された場合、例外がスローされること
     *
     * @return void
     */
    public function testWhenInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DelegationPermissionIdentifier('invalid-uuid');
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DelegationPermissionIdentifier('');
    }
}
