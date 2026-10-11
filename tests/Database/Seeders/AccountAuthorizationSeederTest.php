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
    public function testRequestCreationPoliciesAreOwnerOnlyAndReseedingRemovesAdminAttachment(): void
    {
        $this->artisan('db:seed', ['--class' => AccountAuthorizationSeeder::class]);

        $ownerPolicies = $this->policyNames(Role::OWNER);
        $adminPolicies = $this->policyNames(Role::ADMINISTRATOR);

        $this->assertContains('AFFILIATION_REQUEST_CREATE', $ownerPolicies);
        $this->assertContains('DELEGATION_REQUEST_CREATE', $ownerPolicies);
        $this->assertNotContains('AFFILIATION_REQUEST_CREATE', $adminPolicies);
        $this->assertNotContains('DELEGATION_REQUEST_CREATE', $adminPolicies);
        $this->assertContains('AFFILIATION_REQUEST_RECEIVE', $adminPolicies);
        $this->assertContains('AFFILIATION_APPROVE', $adminPolicies);
        $this->assertContains('AFFILIATION_REJECT', $adminPolicies);

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
        $this->assertContains('ACCOUNT_PRINCIPAL_GROUP_MANAGE', $this->policyNames(Role::ADMINISTRATOR));
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
