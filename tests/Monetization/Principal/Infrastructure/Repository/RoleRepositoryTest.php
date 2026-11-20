<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Policy;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Monetization\Principal\Infrastructure\Repository\PolicyRepository;
use Source\Monetization\Principal\Infrastructure\Repository\RoleRepository;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class RoleRepositoryTest extends TestCase
{
    public function testSaveAndLoadRoundTrip(): void
    {
        $id = new RoleIdentifier('69200000-0000-7000-8000-000000000099');
        $repository = new RoleRepository();
        $repository->save(new Role($id, 'test', [], null));
        $entities = $repository->findByIds([$id]);
        self::assertCount(1, $entities);
        $entity = $entities[(string) $id];
        self::assertSame([], $repository->findByIds([]));
        self::assertSame('test', $entity->name());
    }

    public function testFindSystemByNameExcludesAccountRolesAndLoadsPolicies(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $repository = new RoleRepository();
        $repository->save(new Role(new RoleIdentifier(StrTestHelper::generateUuid()), 'General', [], $account));
        self::assertNull($repository->findSystemByName('General'));

        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        (new PolicyRepository())->save(new Policy($policyIdentifier, 'general-policy', [], null));
        $identifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $repository->save(new Role($identifier, 'General', [$policyIdentifier], null));
        $role = $repository->findSystemByName('General');
        self::assertNotNull($role);
        self::assertSame((string) $identifier, (string) $role->roleIdentifier());
        self::assertTrue($role->isSystemRole());
        self::assertSame([(string) $policyIdentifier], array_map(static fn (PolicyIdentifier $id): string => (string) $id, $role->policies()));
    }

    public function testSaveReplacesPoliciesAndCanClearThemWithoutAffectingOtherRoles(): void
    {
        $policyRepository = new PolicyRepository();
        $policyIds = [new PolicyIdentifier(StrTestHelper::generateUuid()), new PolicyIdentifier(StrTestHelper::generateUuid())];
        foreach ($policyIds as $index => $policyId) {
            $policyRepository->save(new Policy($policyId, 'policy-' . $index, [], null));
        }
        $roleId = new RoleIdentifier(StrTestHelper::generateUuid());
        $otherRoleId = new RoleIdentifier(StrTestHelper::generateUuid());
        $repository = new RoleRepository();
        $role = new Role($roleId, 'Manager', $policyIds, null);
        $repository->save($role);
        $repository->save(new Role($otherRoleId, 'Other', $policyIds, null));
        $repository->save($role);
        self::assertEqualsCanonicalizing($policyIds, $repository->findByIds([$roleId])[(string) $roleId]->policies());

        $roles = $repository->findByIds([$otherRoleId, $roleId, $roleId, new RoleIdentifier(StrTestHelper::generateUuid())]);
        self::assertEqualsCanonicalizing([(string) $roleId, (string) $otherRoleId], array_keys($roles));
        self::assertSame((string) $otherRoleId, (string) $roles[(string) $otherRoleId]->roleIdentifier());

        $role->removePolicy(new PolicyIdentifier((string) $policyIds[0]));
        $repository->save($role);
        self::assertEquals([$policyIds[1]], $repository->findByIds([$roleId])[(string) $roleId]->policies());

        $role->removePolicy($policyIds[1]);
        $repository->save($role);
        self::assertSame([], $repository->findByIds([$roleId])[(string) $roleId]->policies());
        self::assertEqualsCanonicalizing($policyIds, $repository->findByIds([$otherRoleId])[(string) $otherRoleId]->policies());
    }
}
