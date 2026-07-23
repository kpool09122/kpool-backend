<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Infrastructure\Repository;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Account\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Account\Principal\Domain\ValueObject\AccountRole;
use Source\Account\Principal\Domain\ValueObject\PolicyIdentifier;
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
    public function testSaveAndFindByRoles(): void
    {
        $policyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $this->createPolicy($policyIdentifier);

        $repository->save(new Role(AccountRole::ADMIN, [$policyIdentifier]));

        $this->assertDatabaseHas('account_role_policy_attachments', [
            'role' => AccountRole::ADMIN->value,
            'policy_id' => (string) $policyIdentifier,
        ]);

        $roles = $repository->findByRoles([AccountRole::ADMIN]);

        $this->assertArrayHasKey(AccountRole::ADMIN->value, $roles);
        $this->assertTrue($roles[AccountRole::ADMIN->value]->hasPolicy($policyIdentifier));
    }

    #[Group('useDb')]
    public function testSaveSynchronizesPolicies(): void
    {
        $oldPolicyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $newPolicyIdentifier = new PolicyIdentifier(StrTestHelper::generateUuid());
        $repository = $this->app->make(RoleRepositoryInterface::class);
        $this->createPolicy($oldPolicyIdentifier);
        $this->createPolicy($newPolicyIdentifier);

        $repository->save(new Role(AccountRole::OWNER, [$oldPolicyIdentifier]));
        $repository->save(new Role(AccountRole::OWNER, [$newPolicyIdentifier]));

        $this->assertDatabaseMissing('account_role_policy_attachments', [
            'role' => AccountRole::OWNER->value,
            'policy_id' => (string) $oldPolicyIdentifier,
        ]);
        $this->assertDatabaseHas('account_role_policy_attachments', [
            'role' => AccountRole::OWNER->value,
            'policy_id' => (string) $newPolicyIdentifier,
        ]);
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
