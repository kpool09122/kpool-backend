<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Entity;

use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testAddPolicyDoesNotDuplicatePolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $role = new Role($roleIdentifier, Role::OWNER, [], true);

        $role->addPolicy($policyIdentifier);
        $role->addPolicy($policyIdentifier);

        $this->assertSame($roleIdentifier, $role->roleIdentifier());
        $this->assertSame(Role::OWNER, $role->name());
        $this->assertTrue($role->isSystemRole());
        $this->assertCount(1, $role->policies());
        $this->assertTrue($role->hasPolicy($policyIdentifier));
    }

    public function testRemovePolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            Role::ADMIN,
            [$policyIdentifier],
            true,
        );

        $role->removePolicy($policyIdentifier);

        $this->assertSame([], $role->policies());
        $this->assertFalse($role->hasPolicy($policyIdentifier));
    }
}
