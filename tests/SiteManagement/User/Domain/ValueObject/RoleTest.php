<?php

declare(strict_types=1);

namespace SiteManagement\User\Domain\ValueObject;

use Source\SiteManagement\User\Domain\ValueObject\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    /**
     * 正常系: 正しくisAdminメソッドを利用できること.
     *
     * @return void
     */
    public function testIsAdmin(): void
    {
        $role = Role::ADMIN;
        $this->assertSame('admin', $role->value);
        $this->assertTrue($role->isAdmin());

        $role = Role::NONE;
        $this->assertSame('none', $role->value);
        $this->assertFalse($role->isAdmin());
    }
}
