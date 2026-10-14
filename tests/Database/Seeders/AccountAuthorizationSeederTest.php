<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\AccountAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Role;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class AccountAuthorizationSeederTest extends TestCase
{
    #[Group('useDb')]
    public function testReseedingDeletesLegacySystemAdministratorAndPreservesCustomRoles(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $ownerPolicies = $this->policyNames(Role::OWNER);
        $this->assertCount(13, $ownerPolicies);
        $accountId = StrTestHelper::generateUuid();
        CreateAccount::create($accountId);
        $adminRoleId = StrTestHelper::generateUuid();
        $customRoleId = StrTestHelper::generateUuid();
        foreach ([$adminRoleId => null, $customRoleId => $accountId] as $roleId => $roleAccountId) {
            DB::table('account_roles')->insert(['id' => $roleId, 'account_id' => $roleAccountId, 'name' => 'Administrator', 'created_at' => now(), 'updated_at' => now()]);
        }
        $groupId = StrTestHelper::generateUuid();
        DB::table('account_principal_groups')->insert(['id' => $groupId, 'account_id' => $accountId, 'name' => 'Legacy group', 'is_default' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $groupId, 'role_id' => $adminRoleId]);
        $policyId = DB::table('account_policies')->where('name', 'ACCOUNT_READ')->value('id');
        DB::table('account_role_policy_attachments')->insert(['role_id' => $adminRoleId, 'policy_id' => $policyId]);

        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);

        $this->assertDatabaseMissing('account_roles', ['id' => $adminRoleId]);
        $this->assertDatabaseMissing('account_role_policy_attachments', ['role_id' => $adminRoleId]);
        $this->assertDatabaseMissing('account_principal_group_role_attachments', ['role_id' => $adminRoleId]);
        $this->assertDatabaseHas('account_roles', ['id' => $customRoleId, 'account_id' => $accountId, 'name' => 'Administrator']);
        $this->assertDatabaseHas('account_principal_groups', ['id' => $groupId]);
        $this->assertEqualsCanonicalizing($ownerPolicies, $this->policyNames(Role::OWNER));
        $this->assertContains('ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE', $this->policyNames(Role::OPERATIONS));
    }

    #[Group('useDb')]
    public function testNotificationSettingsPoliciesMatchExistingAccountRoleAssignments(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);

        foreach ([Role::OWNER, Role::OPERATIONS] as $role) {
            $policies = $this->policyNames($role);
            $this->assertSame(in_array('ACCOUNT_READ', $policies, true), in_array('ACCOUNT_NOTIFICATION_SETTINGS_READ', $policies, true));
            $this->assertSame(in_array('ACCOUNT_UPDATE', $policies, true), in_array('ACCOUNT_NOTIFICATION_SETTINGS_UPDATE', $policies, true));
        }
        foreach (['READ' => 'read', 'UPDATE' => 'update'] as $name => $action) {
            $json = DB::table('account_policies')->where('name', 'ACCOUNT_NOTIFICATION_SETTINGS_' . $name)->value('statements');
            self::assertIsString($json);
            $this->assertSame([
                ['effect' => 'allow', 'actions' => ['account:notification-settings:' . $action], 'resource_types' => ['account'], 'condition' => null],
            ], json_decode($json, true, flags: JSON_THROW_ON_ERROR));
        }
    }

    #[Group('useDb')]
    public function testRequestCreationPoliciesAreGrantedToOwner(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $ownerPolicies = $this->policyNames(Role::OWNER);
        $this->assertContains('AFFILIATION_REQUEST_CREATE', $ownerPolicies);
        $this->assertContains('DELEGATION_REQUEST_CREATE', $ownerPolicies);
    }

    #[Group('useDb')]
    public function testPrincipalGroupManagementIsGrantedWithoutAccountTypeCondition(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $this->assertContains('ACCOUNT_PRINCIPAL_GROUP_MANAGE', $this->policyNames(Role::OWNER));
        $statementsJson = DB::table('account_policies')
            ->where('name', 'ACCOUNT_PRINCIPAL_GROUP_MANAGE')->value('statements');
        $this->assertIsString($statementsJson);
        $statements = json_decode($statementsJson, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($statements);
        $this->assertArrayHasKey(0, $statements);
        $statement = $statements[0];
        $this->assertIsArray($statement);
        $this->assertSame('allow', $statement['effect']);
        $this->assertSame(['account:principal-group:manage'], $statement['actions']);
        $this->assertNull($statement['condition']);
    }

    /** @return array<array-key, string> */
    private function policyNames(string $roleName): array
    {
        return DB::table('account_policies')
            ->join('account_role_policy_attachments', 'account_policies.id', '=', 'account_role_policy_attachments.policy_id')
            ->join('account_roles', 'account_roles.id', '=', 'account_role_policy_attachments.role_id')
            ->where('account_roles.name', $roleName)
            ->pluck('account_policies.name')
            ->map(static function (mixed $name): string {
                self::assertIsString($name);

                return $name;
            })
            ->all();
    }
}
