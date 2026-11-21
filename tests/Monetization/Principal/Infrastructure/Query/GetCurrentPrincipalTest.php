<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Query;

use Database\Seeders\MonetizationAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class GetCurrentPrincipalTest extends TestCase
{
    public function testCountsOnlyCurrentAccountPrincipalsIncludingStoppedMembers(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        DB::table('monetization_principals')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        $query = $this->app()->make(GetCurrentPrincipalInterface::class);
        $input = new GetCurrentPrincipalInput($identity, $account);
        self::assertSame(1, $query->process($input)->toArray()['accountPrincipalCount']);

        foreach (['active', 'suspended', 'forced_stop'] as $status) {
            $memberIdentity = new IdentityIdentifier(StrTestHelper::generateUuid());
            CreateIdentity::create($memberIdentity, ['email' => $status . '@example.com']);
            DB::table('monetization_principals')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $memberIdentity, 'account_id' => (string) $account, 'status' => $status]);
        }
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        DB::table('monetization_principals')->insert(['id' => StrTestHelper::generateUuid(), 'identity_id' => (string) $identity, 'account_id' => (string) $otherAccount, 'status' => 'active']);

        self::assertSame(4, $query->process($input)->toArray()['accountPrincipalCount']);
    }

    public function testUnenabledPrincipalIsNotFound(): void
    {
        $input = new GetCurrentPrincipalInput(new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(GetCurrentPrincipalInterface::class)->process($input);
    }

    public function testDeduplicatesGrantedPoliciesAndPreservesConditionsAndDeny(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $principalId = StrTestHelper::generateUuid();
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        DB::table('monetization_principals')->insert(['id' => $principalId, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        foreach (['First', 'Second'] as $name) {
            $groupId = StrTestHelper::generateUuid();
            DB::table('monetization_principal_groups')->insert(['id' => $groupId, 'account_id' => (string) $account, 'name' => $name]);
            DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => $groupId, 'principal_id' => $principalId]);
            DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => $groupId, 'role_id' => '09290000-0000-7000-8000-000000000004']);
        }
        $clause = ['key' => 'resource:accountId', 'operator' => 'in', 'value' => [(string) $account]];
        DB::table('monetization_policies')->where('id', '09290000-0000-7000-8000-000000000001')->update(['statements' => json_encode([['effect' => 'deny', 'actions' => ['principal_group_read'], 'resource_types' => ['principal_group'], 'condition' => [$clause]]], JSON_THROW_ON_ERROR)]);

        $result = $this->app()->make(GetCurrentPrincipalInterface::class)->process(new GetCurrentPrincipalInput($identity, $account))->toArray();
        self::assertCount(1, $result['policies']);
        self::assertSame('deny', $result['policies'][0]['statements'][0]['effect']);
        self::assertSame(['clauses' => [['field' => 'resource:accountId', 'operator' => 'in', 'value' => [(string) $account]]]], $result['policies'][0]['statements'][0]['condition']);
    }

    public function testSnapshotReflectsCurrentPermissionsAndStoppedStates(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        $principalId = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $principalId, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        DB::table('account_principal_groups')->insert(['id' => $groupId, 'account_id' => (string) $account, 'name' => 'General', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_group_id' => $groupId, 'principal_id' => $principalId]);
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $this->app()->make(CreatePrincipalInterface::class)->process(new CreatePrincipalInput($identity, $account), new CreatePrincipalOutput());
        $query = $this->app()->make(GetCurrentPrincipalInterface::class);
        self::assertSame($query, $this->app()->make(GetCurrentPrincipalInterface::class));
        $input = new GetCurrentPrincipalInput($identity, $account);
        $result = $query->process($input)->toArray();
        self::assertSame((string) $identity, $result['identityIdentifier']);
        self::assertSame(DB::table('monetization_principals')->value('id'), $result['principalIdentifier']);
        self::assertSame('active', $result['status']);
        self::assertSame([['effect' => 'allow', 'actions' => ['principal_group_read'], 'resourceTypes' => ['principal_group'], 'condition' => null]], $result['policies'][0]['statements']);
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        foreach (['monetization_principal_groups', 'monetization_roles', 'monetization_policies'] as $table) {
            $originalAccount = $table === 'monetization_principal_groups' ? (string) $account : null;
            DB::table($table)->update(['account_id' => (string) $otherAccount]);
            $foreign = $query->process($input)->toArray();
            self::assertSame([], $foreign['policies']);
            DB::table($table)->update(['account_id' => $originalAccount]);
        }

        try {
            $query->process(new GetCurrentPrincipalInput(new IdentityIdentifier(StrTestHelper::generateUuid()), $account));
            self::fail('Another identity must not expose this principal.');
        } catch (PrincipalNotFoundException $exception) {
            self::assertSame('Monetization principal not found.', $exception->getMessage());
        }
        foreach (['suspended', 'forced_stop'] as $status) {
            DB::table('monetization_principals')->update(['status' => $status]);
            $stopped = $query->process($input)->toArray();
            self::assertSame($status, $stopped['status']);
            self::assertSame($result['policies'], $stopped['policies']);
        }
        DB::table('monetization_principals')->update(['status' => 'active']);
        $deny = StrTestHelper::generateUuid();
        DB::table('monetization_policies')->insert(['id' => $deny, 'name' => 'Deny', 'statements' => json_encode([['effect' => 'deny', 'actions' => ['principal_group_read'], 'resource_types' => ['principal_group']]], JSON_THROW_ON_ERROR)]);
        DB::table('monetization_role_policy_attachments')->insert(['role_id' => DB::table('monetization_roles')->where('name', 'General')->value('id'), 'policy_id' => $deny]);
        $denied = $query->process($input)->toArray();
        self::assertCount(2, $denied['policies']);
        DB::table('monetization_principal_group_role_attachments')->delete();
        $empty = $query->process($input)->toArray();
        self::assertSame([], $empty['policies']);
        $this->expectException(PrincipalNotFoundException::class);
        $query->process(new GetCurrentPrincipalInput($identity, new AccountIdentifier(StrTestHelper::generateUuid())));
    }
}
