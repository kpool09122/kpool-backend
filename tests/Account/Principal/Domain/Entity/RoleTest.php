<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Entity;

use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testAddPolicyDoesNotDuplicatePolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = new Role(AccountRole::OWNER, []);

        $role->addPolicy($policyIdentifier);
        $role->addPolicy($policyIdentifier);

        $this->assertSame(AccountRole::OWNER, $role->role());
        $this->assertCount(1, $role->policies());
        $this->assertTrue($role->hasPolicy($policyIdentifier));
    }

    public function testRemovePolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = new Role(AccountRole::ADMIN, [$policyIdentifier]);

        $role->removePolicy($policyIdentifier);

        $this->assertSame([], $role->policies());
        $this->assertFalse($role->hasPolicy($policyIdentifier));
    }
}
