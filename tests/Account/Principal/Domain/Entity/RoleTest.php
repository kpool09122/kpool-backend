<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Domain\Entity;

use DateTimeImmutable;
use Source\Account\Principal\Domain\Entity\Policy;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function testAddPolicyDoesNotDuplicatePolicy(): void
    {
        $policy = $this->createPolicy(null);
        $policyIdentifier = $policy->policyIdentifier();
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $role = new Role($roleIdentifier, Role::OWNER, [], null);

        $role->addPolicy($policy);
        $role->addPolicy($policy);

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
            null,
        );

        $role->removePolicy($policyIdentifier);

        $this->assertSame([], $role->policies());
        $this->assertFalse($role->hasPolicy($policyIdentifier));
    }

    public function testAddPolicyRejectsAccountPolicyForSystemRole(): void
    {
        $role = new Role(new RoleIdentifier(StrTestHelper::generateUuid()), Role::OWNER, [], null);

        $this->expectException(\InvalidArgumentException::class);
        $role->addPolicy($this->createPolicy(new \Source\Shared\Domain\ValueObject\AccountIdentifier(StrTestHelper::generateUuid())));
    }

    private function createPolicy(?\Source\Shared\Domain\ValueObject\AccountIdentifier $accountIdentifier): Policy
    {
        return new Policy(
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            'Test Policy',
            [],
            $accountIdentifier,
            new DateTimeImmutable(),
        );
    }
}
