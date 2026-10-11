<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\AccountAuthorizationSeeder;
use Database\Seeders\SiteManagementAuthorizationSeeder;
use Database\Seeders\SystemPolicySeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('useDb')]
class PrincipalRoleNamingTest extends TestCase
{
    public function testFreshInitializationSeparatesOperatorsFromAccountAdministrators(): void
    {
        $this->seed([AccountAuthorizationSeeder::class, SystemPolicySeeder::class, SystemRoleSeeder::class, SiteManagementAuthorizationSeeder::class]);

        $this->assertEqualsCanonicalizing(['Administrator', 'Owner', 'Operations'], DB::table('account_roles')->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Operator', 'Administrator', 'SeniorCollaborator', 'AgencyActor', 'TalentActor', 'Collaborator', 'None'], DB::table('wiki_roles')->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Operator', 'General'], DB::table('site_management_roles')->pluck('name')->all());

        $administratorPolicies = $this->wikiPolicyNames('Administrator');
        $operatorPolicies = $this->wikiPolicyNames('Operator');
        $this->assertSame(['GLOBAL_PRINCIPAL_GROUP_MANAGE'], $administratorPolicies);
        $this->assertContains('GLOBAL_PUBLISH', $operatorPolicies);
        $this->assertContains('GLOBAL_OFFICIAL_CERTIFICATION_READ', $operatorPolicies);
        $this->assertNotContains('GLOBAL_PRINCIPAL_GROUP_MANAGE', $operatorPolicies);
        $this->assertSame([], $this->wikiPolicyNames('None'));

        $operatorPolicy = DB::table('site_management_policies')
            ->join('site_management_role_policy_attachments as attachments', 'attachments.policy_id', '=', 'site_management_policies.id')
            ->join('site_management_roles as roles', 'roles.id', '=', 'attachments.role_id')
            ->where('roles.name', 'Operator')->first(['site_management_policies.name', 'statements']);
        $this->assertNotNull($operatorPolicy);
        $this->assertSame('administrator', $operatorPolicy->name);
        $this->assertIsString($operatorPolicy->statements);
        $statements = json_decode($operatorPolicy->statements, true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($statements);
        $this->assertIsArray($statements[0]);
        $this->assertNull($statements[0]['condition']);
        $this->assertEqualsCanonicalizing(['announcement', 'contact'], $statements[0]['resource_types']);
    }

    /** @return array<string> */
    private function wikiPolicyNames(string $roleName): array
    {
        return DB::table('wiki_policies')
            ->join('wiki_role_policy_attachments', 'wiki_policies.id', '=', 'wiki_role_policy_attachments.policy_id')
            ->join('wiki_roles', 'wiki_roles.id', '=', 'wiki_role_policy_attachments.role_id')
            ->where('wiki_roles.name', $roleName)->pluck('wiki_policies.name')
            ->map(static function (mixed $name): string {
                self::assertIsString($name);

                return $name;
            })->all();
    }
}
