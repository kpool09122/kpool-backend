<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Domain\Entity;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Tests\Helper\SiteManagementAdministratorTestData;

class PrincipalGroupTest extends TestCase
{
    public function testEntityPreservesIdentityAndData(): void
    {
        $id = new PrincipalGroupIdentifier('69200000-0000-7000-8000-000000000099');
        $entity = new PrincipalGroup($id, 'test', [], new AccountIdentifier('00000000-0000-7000-8000-000000000009'));
        self::assertSame($id, $entity->principalGroupIdentifier());
        self::assertSame('test', $entity->name());
        self::assertSame([], $entity->members());
    }

    public function testAddingRolesAndMembersIsIdempotentAndPreservesExistingValues(): void
    {
        $data = SiteManagementAdministratorTestData::create();
        $group = $data->administratorGroup;
        self::assertFalse($group->hasRole($data->administratorRole->roleIdentifier()));
        self::assertFalse($group->hasMember($data->siteManagementPrincipal->principalIdentifier()));
        $group->addRole($data->administratorRole);
        $group->addRole($data->administratorRole);
        $group->addMember($data->siteManagementPrincipal->principalIdentifier());
        $group->addMember($data->siteManagementPrincipal->principalIdentifier());
        self::assertTrue($group->hasRole(new RoleIdentifier((string) $data->administratorRole->roleIdentifier())));
        self::assertTrue($group->hasMember(new PrincipalIdentifier((string) $data->siteManagementPrincipal->principalIdentifier())));
        self::assertCount(1, $group->roles());
        self::assertCount(1, $group->members());
    }

    public function testRejectsRolesOwnedByAnotherAccount(): void
    {
        $data = SiteManagementAdministratorTestData::create();
        $role = new Role($data->administratorRole->roleIdentifier(), 'other', [], new AccountIdentifier('00000000-0000-7000-8000-000000000010'));
        $this->expectException(InvalidArgumentException::class);
        $data->administratorGroup->addRole($role);
    }
}
