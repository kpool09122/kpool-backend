<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Domain\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Exception\PrincipalAlreadyMemberException;
use Source\Monetization\Principal\Domain\Exception\PrincipalNotMemberException;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\MonetizationPrincipalTestData;

class PrincipalGroupTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new PrincipalGroup($id, 'test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'), false, null, []);
        self::assertSame($id, $entity->principalGroupIdentifier());
        self::assertSame('test', $entity->name());
        self::assertSame([], $entity->members());
    }

    public function testConstructorMembersAreIndexedAndDeduplicated(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $member = $data->principal->principalIdentifier();
        $group = new PrincipalGroup($data->group->principalGroupIdentifier(), 'test', [], $data->principal->accountIdentifier(), false, null, [$member, new PrincipalIdentifier((string) $member)]);

        self::assertTrue($group->hasMember(new PrincipalIdentifier((string) $member)));
        self::assertSame([(string) $member], array_keys($group->members()));
        self::assertCount(1, $group->members());
    }

    public function testAddingRolesIsIdempotentAndPreservesMembers(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $group = $data->group;
        self::assertFalse($group->hasRole($data->role->roleIdentifier()));
        self::assertFalse($group->hasMember($data->principal->principalIdentifier()));
        $group->addRole($data->role);
        $group->addRole($data->role);
        $group->addMember($data->principal->principalIdentifier());
        self::assertTrue($group->hasRole(new RoleIdentifier((string) $data->role->roleIdentifier())));
        self::assertTrue($group->hasMember(new PrincipalIdentifier((string) $data->principal->principalIdentifier())));
        self::assertCount(1, $group->roles());
        self::assertCount(1, $group->members());
    }

    public function testRejectsRolesOwnedByAnotherAccount(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $role = new Role($data->role->roleIdentifier(), 'other', [], new AccountIdentifier('00000000-0000-7000-8000-000000000010'));
        $this->expectException(InvalidArgumentException::class);
        $data->group->addRole($role);
    }

    public function testRemovingRolePreservesOtherRolesAndReindexesThem(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $firstRole = $data->role;
        $secondRole = new Role(new RoleIdentifier('00000000-0000-7000-8000-000000000010'), 'Second', [], $data->group->accountIdentifier());
        $data->group->addRole($firstRole);
        $data->group->addRole($secondRole);
        $data->group->removeRole(new RoleIdentifier((string) $firstRole->roleIdentifier()));
        self::assertFalse($data->group->hasRole($firstRole->roleIdentifier()));
        self::assertSame([$secondRole->roleIdentifier()], $data->group->roles());
        $data->group->removeRole($firstRole->roleIdentifier());
        self::assertSame([$secondRole->roleIdentifier()], $data->group->roles());
    }

    public function testRemovingMemberPreservesOtherMembers(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $firstMember = $data->principal->principalIdentifier();
        $secondMember = new PrincipalIdentifier('00000000-0000-7000-8000-000000000010');
        $data->group->replaceMembers([$firstMember, $secondMember]);
        $data->group->removeMember(new PrincipalIdentifier((string) $firstMember));
        self::assertFalse($data->group->hasMember($firstMember));
        self::assertSame([(string) $secondMember => $secondMember], $data->group->members());
    }

    public function testAddingExistingMemberThrows(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $member = $data->principal->principalIdentifier();
        $data->group->addMember($member);
        $this->expectException(PrincipalAlreadyMemberException::class);
        $data->group->addMember(new PrincipalIdentifier((string) $member));
    }

    public function testRemovingAbsentMemberThrows(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $this->expectException(PrincipalNotMemberException::class);
        $data->group->removeMember($data->principal->principalIdentifier());
    }

    public function testReplacingMembersDeduplicatesAndRemovesPreviousMembers(): void
    {
        $data = MonetizationPrincipalTestData::create();
        $previousMember = $data->principal->principalIdentifier();
        $member = new PrincipalIdentifier('00000000-0000-7000-8000-000000000010');
        $data->group->addMember($previousMember);
        $data->group->replaceMembers([$member, new PrincipalIdentifier((string) $member)]);
        self::assertFalse($data->group->hasMember($previousMember));
        self::assertCount(1, $data->group->members());
        self::assertTrue($data->group->hasMember($member));
    }
}
