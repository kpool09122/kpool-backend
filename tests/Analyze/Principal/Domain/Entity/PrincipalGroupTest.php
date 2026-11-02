<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Domain\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Analyze\Principal\Domain\Entity\PrincipalGroup;
use Source\Analyze\Principal\Domain\Entity\Role;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Analyze\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\AnalyzePrincipalTestData;

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
        $data = AnalyzePrincipalTestData::create();
        $member = $data->principal->principalIdentifier();
        $group = new PrincipalGroup($data->group->principalGroupIdentifier(), 'test', [], $data->principal->accountIdentifier(), false, null, [$member, new PrincipalIdentifier((string) $member)]);

        self::assertTrue($group->hasMember(new PrincipalIdentifier((string) $member)));
        self::assertSame([(string) $member], array_keys($group->members()));
        $group->addMember($member);
        self::assertCount(1, $group->members());
    }

    public function testAddingRolesAndMembersIsIdempotentAndPreservesExistingValues(): void
    {
        $data = AnalyzePrincipalTestData::create();
        $group = $data->group;
        self::assertFalse($group->hasRole($data->role->roleIdentifier()));
        self::assertFalse($group->hasMember($data->principal->principalIdentifier()));
        $group->addRole($data->role);
        $group->addRole($data->role);
        $group->addMember($data->principal->principalIdentifier());
        $group->addMember($data->principal->principalIdentifier());
        self::assertTrue($group->hasRole(new RoleIdentifier((string) $data->role->roleIdentifier())));
        self::assertTrue($group->hasMember(new PrincipalIdentifier((string) $data->principal->principalIdentifier())));
        self::assertCount(1, $group->roles());
        self::assertCount(1, $group->members());
    }

    public function testRejectsRolesOwnedByAnotherAccount(): void
    {
        $data = AnalyzePrincipalTestData::create();
        $role = new Role($data->role->roleIdentifier(), 'other', [], new AccountIdentifier('00000000-0000-7000-8000-000000000010'));
        $this->expectException(InvalidArgumentException::class);
        $data->group->addRole($role);
    }
}
