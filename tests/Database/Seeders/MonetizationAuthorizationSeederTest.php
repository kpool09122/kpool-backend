<?php

declare(strict_types=1);

namespace Tests\Database\Seeders;

use Database\Seeders\MasterDataSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Domain\Entity\Principal;
use Source\Monetization\Principal\Domain\Entity\PrincipalGroup;
use Source\Monetization\Principal\Domain\Entity\Role;
use Source\Monetization\Principal\Domain\Repository\PolicyRepositoryInterface;
use Source\Monetization\Principal\Domain\Repository\RoleRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\Action;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalStatus;
use Source\Monetization\Principal\Domain\ValueObject\Resource;
use Source\Monetization\Principal\Domain\ValueObject\ResourceType;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class MonetizationAuthorizationSeederTest extends TestCase
{
    public function testMasterInitializationIsIdempotentWithoutEnrollingUsers(): void
    {
        self::assertTrue($this->app()->bound(PolicyEvaluatorInterface::class));
        $this->seed(MasterDataSeeder::class);
        $before = [];
        $tables = [
            'monetization_roles' => ['id', 'account_id', 'name'],
            'monetization_policies' => ['id', 'account_id', 'name', 'statements'],
            'monetization_role_policy_attachments' => ['role_id', 'policy_id'],
        ];
        foreach ($tables as $table => $columns) {
            $before[$table] = DB::table($table)->select($columns)->orderBy($columns[0])->orderBy($columns[1])->get()->all();
            self::assertNotEmpty($before[$table]);
        }
        $this->seed(MasterDataSeeder::class);
        foreach ($before as $table => $rows) {
            $columns = $tables[$table];
            self::assertEquals($rows, DB::table($table)->select($columns)->orderBy($columns[0])->orderBy($columns[1])->get()->all());
        }
        self::assertSame(0, DB::table('monetization_principals')->count());
        self::assertSame(0, DB::table('monetization_principal_groups')->count());
        self::assertSame(0, DB::table('monetization_principal_group_memberships')->count());
    }

    public function testRepeatedMasterInitializationRestoresCanonicalGrants(): void
    {
        $this->seed(MasterDataSeeder::class);
        $roleRepository = $this->app()->make(RoleRepositoryInterface::class);
        $role = $roleRepository->findSystemByName(Role::OWNER);
        self::assertNotNull($role);
        $policyId = (string) $role->policies()[0];
        DB::table('monetization_policies')->where('id', $policyId)->update(['statements' => '[]']);
        DB::table('monetization_role_policy_attachments')->where('role_id', (string) $role->roleIdentifier())->delete();
        $this->seed(MasterDataSeeder::class);
        $restored = $roleRepository->findSystemByName(Role::OWNER);
        self::assertNotNull($restored);
        self::assertSame((string) $role->roleIdentifier(), (string) $restored->roleIdentifier());
        self::assertCount(3, $restored->policies());
        $policies = $this->app()->make(PolicyRepositoryInterface::class)->findByIds($restored->policies());
        foreach ($policies as $policy) {
            self::assertCount(1, $policy->statements());
        }
        self::assertSame($roleRepository, $this->app()->make(RoleRepositoryInterface::class));
        self::assertSame($this->app()->make(PolicyEvaluatorInterface::class), $this->app()->make(PolicyEvaluatorInterface::class));
    }

    public function testOwnerAndGeneralHaveDifferentGroupPermissionsWithinTheirAccount(): void
    {
        $this->seed(MasterDataSeeder::class);
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        $roleRepository = $this->app()->make(RoleRepositoryInterface::class);
        $policyEvaluator = $this->app()->make(PolicyEvaluatorInterface::class);
        foreach ([Role::OWNER, Role::GENERAL] as $name) {
            $role = $roleRepository->findSystemByName($name);
            self::assertNotNull($role);
            $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), new IdentityIdentifier(StrTestHelper::generateUuid()), $account, PrincipalStatus::ACTIVE);
            $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), $name, [$role->roleIdentifier()], $account, $name === Role::GENERAL, null, [$principal->principalIdentifier()]);
            self::assertTrue($policyEvaluator->evaluate($principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, $account), [$group]));
            self::assertSame($name === Role::OWNER, $policyEvaluator->evaluate($principal, Action::PRINCIPAL_GROUP_MANAGE, new Resource(ResourceType::PRINCIPAL_GROUP, $account), [$group]));
            self::assertSame($name === Role::OWNER, $policyEvaluator->evaluate($principal, Action::PAYMENT_METHOD_REGISTER, new Resource(ResourceType::PAYMENT_METHOD, $account), [$group]));
            self::assertFalse($policyEvaluator->evaluate($principal, Action::PRINCIPAL_GROUP_READ, new Resource(ResourceType::PRINCIPAL_GROUP, new AccountIdentifier(StrTestHelper::generateUuid())), [$group]));
        }
    }
}
