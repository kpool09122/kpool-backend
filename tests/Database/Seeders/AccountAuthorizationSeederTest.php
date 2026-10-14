<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\AccountAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Principal\Domain\Entity\Role;
use Tests\TestCase;

class AccountAuthorizationSeederTest extends TestCase
{
    #[Group('useDb')]
    public function testOwnerRetainsPoliciesAndReseedingClearsAdministratorPolicies(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $ownerPolicies = $this->policyNames(Role::OWNER);
        $this->assertCount(13, $ownerPolicies);
        $this->assertSame([], $this->policyNames(Role::ADMINISTRATOR));
        $adminRoleId = DB::table('account_roles')->where('name', Role::ADMINISTRATOR)->value('id');
        self::assertIsString($adminRoleId);
        $this->assertContains('ACCOUNT_CATEGORY_CHANGE_REQUEST_MANAGE', $this->policyNames(Role::OPERATIONS));

        $ownerPolicyIds = DB::table('account_policies')->whereIn('name', $ownerPolicies)->pluck('id');
        foreach ($ownerPolicyIds as $policyId) {
            DB::table('account_role_policy_attachments')->insert(['role_id' => $adminRoleId, 'policy_id' => $policyId]);
        }
        $this->assertEqualsCanonicalizing($ownerPolicies, $this->policyNames(Role::ADMINISTRATOR));
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $this->assertSame([], $this->policyNames(Role::ADMINISTRATOR));
        $this->assertEqualsCanonicalizing($ownerPolicies, $this->policyNames(Role::OWNER));
        $this->assertSame($adminRoleId, DB::table('account_roles')->where('name', Role::ADMINISTRATOR)->value('id'));
    }

    #[Group('useDb')]
    public function testNotificationSettingsPoliciesMatchExistingAccountRoleAssignments(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);

        foreach ([Role::OWNER, Role::ADMINISTRATOR, Role::OPERATIONS] as $role) {
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
    public function testRequestCreationPoliciesAreOwnerOnlyAndReseedingRemovesAdminAttachment(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);

        $ownerPolicies = $this->policyNames(Role::OWNER);
        $adminPolicies = $this->policyNames(Role::ADMINISTRATOR);

        $this->assertContains('AFFILIATION_REQUEST_CREATE', $ownerPolicies);
        $this->assertContains('DELEGATION_REQUEST_CREATE', $ownerPolicies);
        $this->assertNotContains('AFFILIATION_REQUEST_CREATE', $adminPolicies);
        $this->assertNotContains('DELEGATION_REQUEST_CREATE', $adminPolicies);
        $this->assertNotContains('AFFILIATION_REQUEST_RECEIVE', $adminPolicies);
        $this->assertNotContains('AFFILIATION_APPROVE', $adminPolicies);
        $this->assertNotContains('AFFILIATION_REJECT', $adminPolicies);

        $affiliationRequestPolicyId = DB::table('account_policies')->where('name', 'AFFILIATION_REQUEST_CREATE')->value('id');
        $adminRoleId = DB::table('account_roles')->where('name', Role::ADMINISTRATOR)->value('id');
        DB::table('account_role_policy_attachments')->insert([
            'role_id' => $adminRoleId,
            'policy_id' => $affiliationRequestPolicyId,
        ]);

        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);

        $this->assertNotContains('AFFILIATION_REQUEST_CREATE', $this->policyNames(Role::ADMINISTRATOR));
    }

    #[Group('useDb')]
    public function testPrincipalGroupManagementIsGrantedWithoutAccountTypeCondition(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);
        $this->assertContains('ACCOUNT_PRINCIPAL_GROUP_MANAGE', $this->policyNames(Role::OWNER));
        $this->assertNotContains('ACCOUNT_PRINCIPAL_GROUP_MANAGE', $this->policyNames(Role::ADMINISTRATOR));
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
