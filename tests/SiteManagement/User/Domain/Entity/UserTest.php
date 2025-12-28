<?php

declare(strict_types=1);

namespace SiteManagement\User\Domain\Entity;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\Entity\User;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $role = Role::ADMIN;

        $user = new User($userIdentifier, $identityIdentifier, $role);

        $this->assertSame($userIdentifier, $user->userIdentifier());
        $this->assertSame($identityIdentifier, $user->identityIdentifier());
        $this->assertSame($role, $user->role());
        $this->assertTrue($user->isAdmin());
    }

    /**
     * 正常系: 正しくロールを変更できること.
     *
     * @return void
     */
    public function testSetRole(): void
    {
        $userIdentifier = new UserIdentifier(StrTestHelper::generateUlid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUlid());
        $role = Role::NONE;

        $user = new User($userIdentifier, $identityIdentifier, $role);
        $this->assertFalse($user->isAdmin());

        $user->setRole(Role::ADMIN);
        $this->assertTrue($user->isAdmin());
    }
}
