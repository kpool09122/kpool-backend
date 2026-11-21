<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Query;

use Database\Seeders\MonetizationAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInput;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\Monetization\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Monetization\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Monetization\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Monetization\Shared\Domain\Exception\UnauthorizedException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\MonetizationPrincipalTestData;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ManagementQueriesTest extends TestCase
{
    /** @return array<string, array{bool}> */
    public static function queries(): array
    {
        return ['groups' => [true], 'members' => [false]];
    }

    /** @return array{AccountIdentifier, PrincipalIdentifier, string} */
    private function owner(?IdentityIdentifier $identity = null): array
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        if ($identity === null) {
            $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identity, ['email' => (string) $identity . '@example.com']);
        }
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $group = StrTestHelper::generateUuid();
        DB::table('monetization_principals')->insert(['id' => (string) $principal, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        DB::table('monetization_principal_groups')->insert(['id' => $group, 'account_id' => (string) $account, 'name' => 'Owners', 'is_default' => false, 'created_at' => now()]);
        DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => $group, 'principal_id' => (string) $principal]);
        DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => '09290000-0000-7000-8000-000000000003']);

        return [$account, $principal, $group];
    }

    #[DataProvider('queries')]
    public function testFreshAuthorizationRejectsMissingStoppedReadOnlyAndExplicitDeny(bool $groups): void
    {
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        [$account, $principal, $group] = $this->owner();
        $query = $groups ? $this->app()->make(ListPrincipalGroupsInterface::class) : $this->app()->make(ListMembersInterface::class);
        $input = $groups ? new ListPrincipalGroupsInput($account, $principal) : new ListMembersInput($account, $principal);
        self::assertCount(1, $query->process($input));
        foreach (['suspended', 'forced_stop', 'read_only', 'no_grant', 'deny', 'missing'] as $case) {
            DB::table('monetization_principals')->where('id', (string) $principal)->update(['status' => in_array($case, ['suspended', 'forced_stop'], true) ? $case : 'active']);
            DB::table('monetization_principal_group_role_attachments')->where('principal_group_id', $group)->update(['role_id' => in_array($case, ['read_only', 'no_grant'], true) ? '09290000-0000-7000-8000-000000000004' : '09290000-0000-7000-8000-000000000003']);
            if ($case === 'no_grant') {
                DB::table('monetization_principal_group_role_attachments')->where('principal_group_id', $group)->delete();
            }
            if ($case === 'deny') {
                DB::table('monetization_principal_group_role_attachments')->insertOrIgnore(['principal_group_id' => $group, 'role_id' => '09290000-0000-7000-8000-000000000003']);
                DB::table('monetization_policies')->where('id', '09290000-0000-7000-8000-000000000002')->update(['statements' => json_encode([['effect' => 'allow', 'actions' => ['principal_group_manage'], 'resource_types' => ['principal_group']], ['effect' => 'deny', 'actions' => ['principal_group_manage'], 'resource_types' => ['principal_group']]], JSON_THROW_ON_ERROR)]);
            }
            if ($case === 'missing') {
                DB::table('monetization_principals')->where('id', (string) $principal)->delete();
            }

            try {
                $query->process($input);
                self::fail('Must reject: ' . $case);
            } catch (UnauthorizedException $exception) {
                self::assertSame('Monetization operation is not authorized.', $exception->getMessage(), $case);
            }
        }
    }

    #[DataProvider('queries')]
    public function testAccountSwitchCannotReuseOriginalPermissionsAndReturnRestoresOriginalScope(bool $groups): void
    {
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        [$original, $originalPrincipal] = $this->owner($identity);
        [$target, $targetPrincipal, $targetGroup] = $this->owner($identity);
        $query = $groups ? $this->app()->make(ListPrincipalGroupsInterface::class) : $this->app()->make(ListMembersInterface::class);
        $input = static fn (AccountIdentifier $account, PrincipalIdentifier $principal) => $groups ? new ListPrincipalGroupsInput($account, $principal) : new ListMembersInput($account, $principal);
        $before = array_map(static fn ($row): array => $row->toArray(), $query->process($input($original, $originalPrincipal)));
        self::assertNotSame($before, array_map(static fn ($row): array => $row->toArray(), $query->process($input($target, $targetPrincipal))));

        try {
            $query->process($input($target, $originalPrincipal));
            self::fail('Foreign principal cannot authorize target account.');
        } catch (UnauthorizedException $exception) {
            self::assertSame('Monetization operation is not authorized.', $exception->getMessage());
        }
        DB::table('monetization_principal_group_role_attachments')->where('principal_group_id', $targetGroup)->update(['role_id' => '09290000-0000-7000-8000-000000000004']);

        try {
            $query->process($input($target, $targetPrincipal));
            self::fail('Original Owner grant must not leak into target General grant.');
        } catch (UnauthorizedException $exception) {
            self::assertSame('Monetization operation is not authorized.', $exception->getMessage());
        }
        self::assertSame($before, array_map(static fn ($row): array => $row->toArray(), $query->process($input($original, $originalPrincipal))));
    }

    public function testGroupsFilterForeignMembershipsAndRolesAndOrderEveryCollection(): void
    {
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        [$account, $principal, $ownerGroup] = $this->owner();
        [$foreign, $foreignPrincipal] = $this->owner();
        $foreignRole = StrTestHelper::generateUuid();
        DB::table('monetization_roles')->insert(['id' => $foreignRole, 'account_id' => (string) $foreign, 'name' => 'Other']);
        DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => $ownerGroup, 'principal_id' => (string) $foreignPrincipal]);
        DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => $ownerGroup, 'role_id' => $foreignRole]);
        $emptyGroupIds = [StrTestHelper::generateUuid(), StrTestHelper::generateUuid()];
        foreach ($emptyGroupIds as $id) {
            DB::table('monetization_principal_groups')->insert(['id' => $id, 'account_id' => (string) $account, 'name' => 'Default-' . $id, 'is_default' => true, 'created_at' => '2020-01-01 00:00:00']);
        }
        sort($emptyGroupIds);
        $result = $this->app()->make(ListPrincipalGroupsInterface::class)->process(new ListPrincipalGroupsInput($account, $principal));
        self::assertSame([...$emptyGroupIds, $ownerGroup], array_map(static fn ($group): string => $group->principalGroupIdentifier(), $result));
        self::assertSame([], $result[0]->toArray()['members']);
        self::assertSame([], $result[0]->toArray()['roleIdentifiers']);
        self::assertNull($result[0]->toArray()['description']);
        self::assertTrue($result[0]->toArray()['isDefault']);
        self::assertSame(['09290000-0000-7000-8000-000000000003'], $result[2]->toArray()['roleIdentifiers']);
        self::assertSame([(string) $principal], array_map(static fn ($member): string => $member->principalIdentifier(), $result[2]->members()));
    }

    #[DataProvider('queries')]
    public function testEmptyReadProjectionReturnsArrayWithoutCreatingData(bool $groups): void
    {
        $principal = MonetizationPrincipalTestData::create()->principal;
        $principalRepository = Mockery::mock(PrincipalRepositoryInterface::class);
        $principalRepository->shouldReceive('findById')->once()->with($principal->principalIdentifier())->andReturn($principal);
        $policyEvaluator = Mockery::mock(PolicyEvaluatorInterface::class);
        $policyEvaluator->shouldReceive('evaluate')->once()->andReturnTrue();
        $this->app()->instance(PrincipalRepositoryInterface::class, $principalRepository);
        $this->app()->instance(PolicyEvaluatorInterface::class, $policyEvaluator);
        $query = $groups ? $this->app()->make(ListPrincipalGroupsInterface::class) : $this->app()->make(ListMembersInterface::class);
        $input = $groups ? new ListPrincipalGroupsInput($principal->accountIdentifier(), $principal->principalIdentifier()) : new ListMembersInput($principal->accountIdentifier(), $principal->principalIdentifier());
        self::assertSame([], $query->process($input));
        foreach (['monetization_principals', 'monetization_principal_groups', 'monetization_principal_group_memberships'] as $table) {
            self::assertSame(0, DB::table($table)->count());
        }
    }

    public function testCandidatesContainAllEnrolledStatusesButNotForeignOrUnenrolledIdentities(): void
    {
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        [$account, $principal] = $this->owner();
        $this->owner();
        $expected = [(string) $principal => 'active'];
        foreach (['active', 'suspended', 'forced_stop'] as $status) {
            $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($identity, ['email' => $status . '@example.com']);
            $id = StrTestHelper::generateUuid();
            $expected[$id] = $status;
            DB::table('monetization_principals')->insert(['id' => $id, 'account_id' => (string) $account, 'identity_id' => (string) $identity, 'status' => $status]);
        }
        CreateIdentity::create(new IdentityIdentifier(StrTestHelper::generateUuid()), ['email' => 'unenrolled@example.com']);
        ksort($expected);
        $before = DB::table('monetization_principals')->count();
        $result = array_map(static fn ($member): array => $member->toArray(), $this->app()->make(ListMembersInterface::class)->process(new ListMembersInput($account, $principal)));
        self::assertSame(array_keys($expected), array_column($result, 'principalIdentifier'));
        self::assertSame(array_values($expected), array_column($result, 'status'));
        self::assertSame($before, DB::table('monetization_principals')->count());
        self::assertCount(4, $result);
    }
}
