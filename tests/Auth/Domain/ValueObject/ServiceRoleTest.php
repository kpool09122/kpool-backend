<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\ValueObject\ServiceRole;

class ServiceRoleTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $service = 'auth';
        $role = 'admin';

        $serviceRole = new ServiceRole($service, $role);

        $this->assertSame($service, $serviceRole->service());
        $this->assertSame($role, $serviceRole->role());
    }

    /**
     * 正常系: サービスが空の時、例外がスローされること.
     *
     * @return void
     */
    public function testWithEmptyServiceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ServiceRole('', 'role');
    }

    /**
     * 正常系: ロールが空の時、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithEmptyRoleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ServiceRole('service', '');
    }
}
