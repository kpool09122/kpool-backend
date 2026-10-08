<?php

declare(strict_types=1);

namespace Tests\Models\SiteManagement;

use Application\Models\SiteManagement\Policy;
use Application\Models\SiteManagement\Principal;
use Application\Models\SiteManagement\PrincipalGroup;
use Application\Models\SiteManagement\PrincipalGroupMembership;
use Application\Models\SiteManagement\PrincipalGroupRoleAttachment;
use Application\Models\SiteManagement\Role;
use Application\Models\SiteManagement\RolePolicyAttachment;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AuthorizationModelsTest extends TestCase
{
    public function testPersistsAuthorizationGraphWithTimestampsAndRelations(): void
    {
        $accountId = '00000000-0000-7000-8000-000000000009';
        CreateAccount::create($accountId);
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $principal = Principal::query()->create(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identity, 'account_id' => $accountId]);
        $group = PrincipalGroup::query()->create(['account_id' => $accountId, 'id' => StrTestHelper::generateUuid(), 'name' => 'model-group']);
        $role = Role::query()->create(['id' => StrTestHelper::generateUuid(), 'name' => 'model-role']);
        $statements = [['effect' => 'allow', 'actions' => ['contact:view'], 'resource_types' => ['contact'], 'condition' => 'own_contact']];
        $policy = Policy::query()->create([
            'id' => StrTestHelper::generateUuid(), 'name' => 'model-policy', 'statements' => $statements,
            'unexpected_column' => 'must not be persisted',
        ]);
        $membership = PrincipalGroupMembership::query()->create(['principal_group_id' => $group->id, 'principal_id' => $principal->id]);
        $groupRole = PrincipalGroupRoleAttachment::query()->create(['principal_group_id' => $group->id, 'role_id' => $role->id]);
        $rolePolicy = RolePolicyAttachment::query()->create(['role_id' => $role->id, 'policy_id' => $policy->id]);

        foreach ([$principal, $group, $role, $policy, $membership] as $model) {
            $model->refresh();
            $this->assertInstanceOf(Carbon::class, $model->created_at);
            $this->assertInstanceOf(Carbon::class, $model->updated_at);
        }
        $this->assertSame($statements, $policy->statements);
        $this->assertSame('own_contact', $policy->statementValues()[0]['condition']);
        $this->assertArrayNotHasKey('unexpected_column', $policy->getAttributes());
        $this->assertSame((string) $identity, $principal->identity?->id);
        $this->assertSame($accountId, $principal->account?->id);
        $this->assertSame($accountId, $group->account?->id);
        $this->assertSame($group->id, $principal->memberships->sole()->principal_group_id);
        $this->assertSame($principal->id, $group->memberships->sole()->principal_id);
        $this->assertSame($group->id, $membership->principalGroup?->id);
        $this->assertSame($principal->id, $membership->principal?->id);
        $this->assertSame($role->id, $group->roleAttachments->sole()->role?->id);
        $this->assertSame($role->id, $groupRole->role?->id);
        $this->assertSame($policy->id, $role->policyAttachments->sole()->policy?->id);
        $this->assertSame($policy->id, $rolePolicy->policy?->id);
    }

    public function testAccountPoliciesAndRolesCanUseTheSameNamesAcrossAccounts(): void
    {
        foreach ([1, 2] as $index) {
            $accountId = StrTestHelper::generateUuid();
            CreateAccount::create($accountId, ['email' => 'account-' . $index . '@example.com']);
            $policy = Policy::query()->create(['id' => StrTestHelper::generateUuid(), 'account_id' => $accountId, 'name' => 'custom', 'statements' => []]);
            $role = Role::query()->create(['id' => StrTestHelper::generateUuid(), 'account_id' => $accountId, 'name' => 'custom']);
            $this->assertSame($accountId, $policy->refresh()->account_id);
            $this->assertSame($accountId, $role->refresh()->account_id);
        }
        $this->assertSame(2, Policy::query()->where('name', 'custom')->count());
        $this->assertSame(2, Role::query()->where('name', 'custom')->count());
    }

    public function testDeletesOnlyTheTargetAssociationWithCompositeKeys(): void
    {
        $accountId = '00000000-0000-7000-8000-000000000009';
        CreateAccount::create($accountId);
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        $principal = Principal::query()->create(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identity, 'account_id' => $accountId]);
        $groups = [];
        $roles = [];
        $policies = [];
        foreach ([1, 2] as $index) {
            $groups[] = PrincipalGroup::query()->create(['account_id' => $accountId, 'id' => StrTestHelper::generateUuid(), 'name' => 'group-' . $index]);
            $roles[] = Role::query()->create(['id' => StrTestHelper::generateUuid(), 'name' => 'role-' . $index]);
            $policies[] = Policy::query()->create(['id' => StrTestHelper::generateUuid(), 'name' => 'policy-' . $index, 'statements' => []]);
        }
        $membership = PrincipalGroupMembership::query()->create(['principal_group_id' => $groups[0]->id, 'principal_id' => $principal->id]);
        PrincipalGroupMembership::query()->create(['principal_group_id' => $groups[1]->id, 'principal_id' => $principal->id]);
        $groupRole = PrincipalGroupRoleAttachment::query()->create(['principal_group_id' => $groups[0]->id, 'role_id' => $roles[0]->id]);
        PrincipalGroupRoleAttachment::query()->create(['principal_group_id' => $groups[0]->id, 'role_id' => $roles[1]->id]);
        $rolePolicy = RolePolicyAttachment::query()->create(['role_id' => $roles[0]->id, 'policy_id' => $policies[0]->id]);
        RolePolicyAttachment::query()->create(['role_id' => $roles[0]->id, 'policy_id' => $policies[1]->id]);

        $membership->delete();
        $groupRole->delete();
        $rolePolicy->delete();

        $this->assertDatabaseMissing('site_management_principal_group_memberships', ['principal_group_id' => $groups[0]->id, 'principal_id' => $principal->id]);
        $this->assertDatabaseHas('site_management_principal_group_memberships', ['principal_group_id' => $groups[1]->id, 'principal_id' => $principal->id]);
        $this->assertDatabaseMissing('site_management_principal_group_role_attachments', ['principal_group_id' => $groups[0]->id, 'role_id' => $roles[0]->id]);
        $this->assertDatabaseHas('site_management_principal_group_role_attachments', ['principal_group_id' => $groups[0]->id, 'role_id' => $roles[1]->id]);
        $this->assertDatabaseMissing('site_management_role_policy_attachments', ['role_id' => $roles[0]->id, 'policy_id' => $policies[0]->id]);
        $this->assertDatabaseHas('site_management_role_policy_attachments', ['role_id' => $roles[0]->id, 'policy_id' => $policies[1]->id]);
    }
}
