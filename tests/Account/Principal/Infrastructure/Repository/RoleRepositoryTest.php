<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
use Source\Account\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Account\Principal\Infrastructure\Repository\RoleRepository;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class RoleRepositoryTest extends TestCase
{
    public function test__construct(): void
    {
        $repository = $this->app->make(RoleRepositoryInterface::class);

        $this->assertInstanceOf(RoleRepository::class, $repository);
    }

    #[Group('useDb')]
    public function testSaveAndFindByIds(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $this->createPolicy($policyIdentifier);

        $repository->save(new Role($roleIdentifier, Role::ADMIN, [$policyIdentifier], true));

        $this->assertDatabaseHas('account_roles', [
            'id' => (string) $roleIdentifier,
            'name' => Role::ADMIN,
            'is_system_role' => true,
        ]);

        $this->assertDatabaseHas('account_role_policy_attachments', [
            'role_id' => (string) $roleIdentifier,
            'policy_id' => (string) $policyIdentifier,
        ]);

        $roles = $repository->findByIds([$roleIdentifier]);

        $this->assertArrayHasKey((string) $roleIdentifier, $roles);
        $this->assertSame(Role::ADMIN, $roles[(string) $roleIdentifier]->name());
        $this->assertTrue($roles[(string) $roleIdentifier]->hasPolicy($policyIdentifier));
    }

    #[Group('useDb')]
    public function testSaveSynchronizesPolicies(): void
    {
        $oldPolicyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $newPolicyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $this->createPolicy($oldPolicyIdentifier);
        $this->createPolicy($newPolicyIdentifier);

        $repository->save(new Role($roleIdentifier, Role::OWNER, [$oldPolicyIdentifier], true));
        $repository->save(new Role($roleIdentifier, Role::OWNER, [$newPolicyIdentifier], true));

        $this->assertDatabaseMissing('account_role_policy_attachments', [
            'role_id' => (string) $roleIdentifier,
            'policy_id' => (string) $oldPolicyIdentifier,
        ]);
        $this->assertDatabaseHas('account_role_policy_attachments', [
            'role_id' => (string) $roleIdentifier,
            'policy_id' => (string) $newPolicyIdentifier,
        ]);
    }

    #[Group('useDb')]
    public function testFindByName(): void
    {
        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        $roleName = 'Role ' . (string) $roleIdentifier;
        $repository = $this->app->make(RoleRepositoryInterface::class);

        $repository->save(new Role($roleIdentifier, $roleName, [], true));

        $result = $repository->findByName($roleName);

        $this->assertNotNull($result);
        $this->assertSame((string) $roleIdentifier, (string) $result->roleIdentifier());
        $this->assertSame($roleName, $result->name());
    }

    private function createPolicy(PolicyIdentifier $policyIdentifier): void
    {
        DB::table('account_policies')->insert([
            'id' => (string) $policyIdentifier,
            'name' => 'POLICY_' . str_replace('-', '_', (string) $policyIdentifier),
            'statements' => '[]',
            'is_system_policy' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
