<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\StrTestHelper;

class RoleTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $name = 'Administrator';
        $policies = [
            new PolicyIdentifier(StrTestHelper::generateUuid()),
        ];
        $isSystemRole = true;
        $createdAt = new DateTimeImmutable();

        $role = new Role(
            $roleIdentifier,
            $name,
            $policies,
            $isSystemRole,
            $createdAt,
        );

        $this->assertSame($roleIdentifier, $role->roleIdentifier());
        $this->assertSame($name, $role->name());
        $this->assertSame($policies, $role->policies());
        $this->assertTrue($role->isSystemRole());
        $this->assertSame($createdAt, $role->createdAt());
    }

    /**
     * 正常系: 非システムロールが作成できること
     */
    public function testNonSystemRole(): void
    {
        $role = $this->createRole(isSystemRole: false);

        $this->assertFalse($role->isSystemRole());
    }

    /**
     * 正常系: 複数のPoliciesを持つRoleが作成できること
     */
    public function testRoleWithMultiplePolicies(): void
    {
        $policies = [
            new PolicyIdentifier(StrTestHelper::generateUuid()),
            new PolicyIdentifier(StrTestHelper::generateUuid()),
        ];

        $role = $this->createRole(policies: $policies);

        $this->assertCount(2, $role->policies());
    }

    /**
     * 正常系: 空のPoliciesでRoleが作成できること
     */
    public function testRoleWithEmptyPolicies(): void
    {
        $role = $this->createRole(policies: []);

        $this->assertSame([], $role->policies());
    }

    /**
     * 正常系: addPolicyでPolicyを追加できること
     */
    public function testAddPolicy(): void
    {
        $role = $this->createRole(policies: []);
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());

        $role->addPolicy($policyIdentifier);

        $this->assertCount(1, $role->policies());
        $this->assertTrue($role->hasPolicy($policyIdentifier));
    }

    /**
     * 正常系: 重複するPolicyは追加されないこと
     */
    public function testAddPolicyDuplicate(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = $this->createRole(policies: [$policyIdentifier]);

        $role->addPolicy($policyIdentifier);

        $this->assertCount(1, $role->policies());
    }

    /**
     * 正常系: removePolicyでPolicyを削除できること
     */
    public function testRemovePolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = $this->createRole(policies: [$policyIdentifier]);

        $role->removePolicy($policyIdentifier);

        $this->assertCount(0, $role->policies());
        $this->assertFalse($role->hasPolicy($policyIdentifier));
    }

    /**
     * 正常系: 存在しないPolicyを削除しても例外が発生しないこと
     */
    public function testRemovePolicyNotFound(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = $this->createRole(policies: []);

        $role->removePolicy($policyIdentifier);

        $this->assertCount(0, $role->policies());
    }

    /**
     * 正常系: hasPolicyでPolicyの存在を確認できること
     */
    public function testHasPolicy(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $otherPolicyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $role = $this->createRole(policies: [$policyIdentifier]);

        $this->assertTrue($role->hasPolicy($policyIdentifier));
        $this->assertFalse($role->hasPolicy($otherPolicyIdentifier));
    }

    /**
     * @param PolicyIdentifier[] $policies
     */
    private function createRole(
        ?array $policies = null,
        bool $isSystemRole = false,
    ): Role {
        return new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            'Test Role',
            $policies ?? [
                new PolicyIdentifier(StrTestHelper::generateUuid()),
            ],
            $isSystemRole,
            new DateTimeImmutable(),
        );
    }
}
