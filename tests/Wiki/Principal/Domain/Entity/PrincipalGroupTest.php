<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Wiki\Principal\Domain\Entity\PrincipalGroup;
use Source\Wiki\Principal\Domain\Entity\Role;
use Source\Wiki\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Wiki\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalGroupTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Default Group';
        $isDefault = true;
        $createdAt = new DateTimeImmutable();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $isDefault,
            $createdAt,
        );

        $this->assertSame($principalGroupIdentifier, $principalGroup->principalGroupIdentifier());
        $this->assertSame($accountIdentifier, $principalGroup->accountIdentifier());
        $this->assertSame($name, $principalGroup->name());
        $this->assertTrue($principalGroup->isDefault());
        $this->assertSame($createdAt, $principalGroup->createdAt());
    }

    /**
     * 正常系: 非デフォルトのPrincipalGroupが作成できること
     */
    public function testNonDefaultPrincipalGroup(): void
    {
        $principalGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $name = 'Custom Group';
        $isDefault = false;
        $createdAt = new DateTimeImmutable();

        $principalGroup = new PrincipalGroup(
            $principalGroupIdentifier,
            $accountIdentifier,
            $name,
            $isDefault,
            $createdAt,
        );

        $this->assertFalse($principalGroup->isDefault());
    }

    /**
     * 正常系: 初期状態でメンバーが空であること
     */
    public function testMembersReturnsEmptyArrayByDefault(): void
    {
        $principalGroup = $this->createPrincipalGroup();

        $this->assertSame([], $principalGroup->members());
        $this->assertSame(0, $principalGroup->memberCount());
    }

    /**
     * 正常系: メンバーを追加できること
     */
    public function testAddMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier);

        $this->assertSame(1, $principalGroup->memberCount());
        $this->assertTrue($principalGroup->hasMember($principalIdentifier));
        $this->assertSame($principalIdentifier, $principalGroup->members()[(string) $principalIdentifier]);
    }

    /**
     * 正常系: 複数のメンバーを追加できること
     */
    public function testAddMultipleMembers(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier1 = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $principalIdentifier2 = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier1);
        $principalGroup->addMember($principalIdentifier2);

        $this->assertSame(2, $principalGroup->memberCount());
        $this->assertTrue($principalGroup->hasMember($principalIdentifier1));
        $this->assertTrue($principalGroup->hasMember($principalIdentifier2));
    }

    /**
     * 異常系: 既にメンバーのPrincipalを追加しようとすると例外が発生すること
     */
    public function testAddMemberThrowsExceptionWhenAlreadyMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier);

        $this->expectException(PrincipalAlreadyMemberException::class);
        $this->expectExceptionMessage('Principal is already a member of this group.');

        $principalGroup->addMember($principalIdentifier);
    }

    /**
     * 正常系: メンバーを削除できること
     */
    public function testRemoveMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addMember($principalIdentifier);
        $principalGroup->removeMember($principalIdentifier);

        $this->assertSame(0, $principalGroup->memberCount());
        $this->assertFalse($principalGroup->hasMember($principalIdentifier));
    }

    /**
     * 異常系: メンバーでないPrincipalを削除しようとすると例外が発生すること
     */
    public function testRemoveMemberThrowsExceptionWhenNotMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->expectException(PrincipalNotMemberException::class);
        $this->expectExceptionMessage('Principal is not a member of this group.');

        $principalGroup->removeMember($principalIdentifier);
    }

    /**
     * 正常系: hasMemberがメンバーでない場合falseを返すこと
     */
    public function testHasMemberReturnsFalseWhenNotMember(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());

        $this->assertFalse($principalGroup->hasMember($principalIdentifier));
    }

    /**
     * 正常系: 初期状態でrolesが空であること
     */
    public function testRolesReturnsEmptyArrayByDefault(): void
    {
        $principalGroup = $this->createPrincipalGroup();

        $this->assertSame([], $principalGroup->roles());
    }

    /**
     * 正常系: Roleを追加できること
     */
    public function testAddRole(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $role = $this->createRole($principalGroup->accountIdentifier());
        $roleIdentifier = $role->roleIdentifier();

        $principalGroup->addRole($role);

        $this->assertTrue($principalGroup->hasRole($roleIdentifier));
        $this->assertCount(1, $principalGroup->roles());
    }

    /**
     * 正常系: 複数のRoleを追加できること
     */
    public function testAddMultipleRoles(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $role1 = $this->createRole($principalGroup->accountIdentifier());
        $role2 = $this->createRole($principalGroup->accountIdentifier());
        $roleIdentifier1 = $role1->roleIdentifier();
        $roleIdentifier2 = $role2->roleIdentifier();

        $principalGroup->addRole($role1);
        $principalGroup->addRole($role2);

        $this->assertCount(2, $principalGroup->roles());
        $this->assertTrue($principalGroup->hasRole($roleIdentifier1));
        $this->assertTrue($principalGroup->hasRole($roleIdentifier2));
    }

    /**
     * 正常系: 重複するRoleは追加されないこと（冪等性）
     */
    public function testAddRoleDuplicate(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $role = $this->createRole($principalGroup->accountIdentifier());

        $principalGroup->addRole($role);
        $principalGroup->addRole($role);

        $this->assertCount(1, $principalGroup->roles());
    }

    /**
     * 正常系: Roleを削除できること
     */
    public function testRemoveRole(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $role = $this->createRole($principalGroup->accountIdentifier());
        $roleIdentifier = $role->roleIdentifier();

        $principalGroup->addRole($role);
        $principalGroup->removeRole($roleIdentifier);

        $this->assertFalse($principalGroup->hasRole($roleIdentifier));
        $this->assertCount(0, $principalGroup->roles());
    }

    /**
     * 正常系: 存在しないRoleを削除しても例外が発生しないこと（冪等性）
     */
    public function testRemoveRoleNotFound(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $role = $this->createRole($principalGroup->accountIdentifier());
        $roleIdentifier = $role->roleIdentifier();

        $principalGroup->removeRole($roleIdentifier);

        $this->assertCount(0, $principalGroup->roles());
    }

    /**
     * 正常系: hasRoleでRoleの存在を確認できること
     */
    public function testHasRole(): void
    {
        $principalGroup = $this->createPrincipalGroup();
        $role = $this->createRole($principalGroup->accountIdentifier());
        $roleIdentifier = $role->roleIdentifier();
        $otherRoleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());

        $principalGroup->addRole($role);

        $this->assertTrue($principalGroup->hasRole($roleIdentifier));
        $this->assertFalse($principalGroup->hasRole($otherRoleIdentifier));
    }

    public function testAddRoleRejectsRoleFromAnotherAccount(): void
    {
        $principalGroup = $this->createPrincipalGroup();

        $this->expectException(\InvalidArgumentException::class);
        $principalGroup->addRole($this->createRole(new AccountIdentifier(StrTestHelper::generateUuid())));
    }

    private function createPrincipalGroup(): PrincipalGroup
    {
        return new PrincipalGroup(
            new PrincipalGroupIdentifier(StrTestHelper::generateUuid()),
            new AccountIdentifier(StrTestHelper::generateUuid()),
            'Test Group',
            false,
            new DateTimeImmutable(),
        );
    }

    private function createRole(?AccountIdentifier $accountIdentifier): Role
    {
        return new Role(
            new RoleIdentifier(StrTestHelper::generateUuid()),
            'Test Role',
            [],
            $accountIdentifier,
            new DateTimeImmutable(),
        );
    }
}
