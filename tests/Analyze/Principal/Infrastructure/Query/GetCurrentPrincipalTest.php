<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Infrastructure\Query;

use Database\Seeders\AnalyzeAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Analyze\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInput;
use Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal\GetCurrentPrincipalInterface;
use Source\Analyze\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class GetCurrentPrincipalTest extends TestCase
{
    public function testUnenabledPrincipalIsNotFound(): void
    {
        $input = new GetCurrentPrincipalInput(new IdentityIdentifier(StrTestHelper::generateUuid()), new AccountIdentifier(StrTestHelper::generateUuid()));
        $this->expectException(PrincipalNotFoundException::class);
        $this->app()->make(GetCurrentPrincipalInterface::class)->process($input);
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
        $this->app()->make(AnalyzeAuthorizationSeeder::class)->run();
        $this->app()->make(CreatePrincipalInterface::class)->process(new CreatePrincipalInput($identity, $account), new CreatePrincipalOutput());
        $query = $this->app()->make(GetCurrentPrincipalInterface::class);
        self::assertSame($query, $this->app()->make(GetCurrentPrincipalInterface::class));
        $input = new GetCurrentPrincipalInput($identity, $account);
        $result = $query->process($input)->toArray();
        self::assertSame((string) $identity, $result['identityIdentifier']);
        self::assertSame(DB::table('analyze_principals')->value('id'), $result['principalIdentifier']);
        self::assertSame('active', $result['status']);
        self::assertSame([['effect' => 'allow', 'actions' => ['analysis:view'], 'resourceTypes' => ['analysis']]], $result['policies'][0]['statements']);
        $otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $otherAccount);
        foreach (['analyze_principal_groups', 'analyze_roles', 'analyze_policies'] as $table) {
            $originalAccount = $table === 'analyze_principal_groups' ? (string) $account : null;
            DB::table($table)->update(['account_id' => (string) $otherAccount]);
            $foreign = $query->process($input)->toArray();
            self::assertSame([], $foreign['policies']);
            DB::table($table)->update(['account_id' => $originalAccount]);
        }

        try {
            $query->process(new GetCurrentPrincipalInput(new IdentityIdentifier(StrTestHelper::generateUuid()), $account));
            self::fail('Another identity must not expose this principal.');
        } catch (PrincipalNotFoundException $exception) {
            self::assertSame('Analyze principal not found.', $exception->getMessage());
        }
        foreach (['suspended', 'forced_stop'] as $status) {
            DB::table('analyze_principals')->update(['status' => $status]);
            $stopped = $query->process($input)->toArray();
            self::assertSame($status, $stopped['status']);
            self::assertSame($result['policies'], $stopped['policies']);
        }
        DB::table('analyze_principals')->update(['status' => 'active']);
        $deny = StrTestHelper::generateUuid();
        DB::table('analyze_policies')->insert(['id' => $deny, 'name' => 'Deny', 'statements' => json_encode([['effect' => 'deny', 'actions' => ['analysis:view'], 'resource_types' => ['analysis']]], JSON_THROW_ON_ERROR)]);
        DB::table('analyze_role_policy_attachments')->insert(['role_id' => DB::table('analyze_roles')->value('id'), 'policy_id' => $deny]);
        $denied = $query->process($input)->toArray();
        self::assertCount(2, $denied['policies']);
        DB::table('analyze_principal_group_role_attachments')->delete();
        $empty = $query->process($input)->toArray();
        self::assertSame([], $empty['policies']);
        $this->expectException(PrincipalNotFoundException::class);
        $query->process(new GetCurrentPrincipalInput($identity, new AccountIdentifier(StrTestHelper::generateUuid())));
    }
}
