<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Infrastructure\Repository;

use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Policy;
use Source\SiteManagement\Principal\Domain\Entity\Role;
use Source\SiteManagement\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PolicyRepository;
use Source\SiteManagement\Principal\Infrastructure\Repository\RoleRepository;
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
        $repository->save(new Role($id, 'test', []));
        $entities = $repository->findByIds([$id]);
        self::assertCount(1, $entities);
        $entity = $entities[0];
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
        (new PolicyRepository())->save(new Policy($policyIdentifier, 'general-policy', []));
        $identifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $repository->save(new Role($identifier, 'General', [$policyIdentifier]));
        $role = $repository->findSystemByName('General');
        self::assertNotNull($role);
        self::assertSame((string) $identifier, (string) $role->roleIdentifier());
        self::assertTrue($role->isSystemRole());
        self::assertSame([(string) $policyIdentifier], array_map(static fn (PolicyIdentifier $id): string => (string) $id, $role->policies()));
    }
}
