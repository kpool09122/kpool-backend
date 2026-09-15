<?php

declare(strict_types=1);

namespace Tests\Identity\Infrastructure\Query;

use Database\Seeders\AccountAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Group;
use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInput;
use Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity\GetAuthenticatedIdentityInterface;
use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateAccountPrincipalGroup;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class GetAuthenticatedIdentityTest extends TestCase
{
    #[Group('useDb')]
    public function testProcessReturnsAuthenticatedIdentity(): void
    {
        $accountIdentifier = new AccountIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5aa');
        $principalGroupIdentifier = new PrincipalGroupIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5bb');
        $principalIdentifier = '019de7f3-78f3-7b55-9ed5-17f63e14d5cc';
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        CreateAccount::create((string) $accountIdentifier, ['type' => 'corporation']);
        $this->app->make(AccountAuthorizationSeeder::class)->run();
        CreateIdentity::create($identityIdentifier, [
            'identity_name' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'profile_image' => 'profile/test.png',
        ]);
        $ownerRoleId = DB::table('account_roles')->where('name', 'Owner')->value('id');
        $this->assertIsString($ownerRoleId);

        CreateAccountPrincipalGroup::create($principalGroupIdentifier, $accountIdentifier, [
            'role_ids' => [$ownerRoleId],
        ]);
        DB::table('account_principals')->insert([
            'id' => $principalIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $accountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('account_principal_group_memberships')->insert([
            'id' => StrTestHelper::generateUuid(),
            'principal_group_id' => (string) $principalGroupIdentifier,
            'principal_id' => $principalIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('set')->once();

        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);
        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('test-user', $readModel->identityName());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame('ja', $readModel->language());
        $this->assertSame('http://127.0.0.1:8080/storage/profile/test.png', $readModel->profileImage());
        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5aa', $readModel->accountIdentifier());
        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5cc', $readModel->accountPrincipalIdentifier());
        $this->assertSame('corporation', $readModel->accountType());
        $this->assertGreaterThanOrEqual(4, count($readModel->accountPolicies()));
        $statements = array_merge(...array_column($readModel->accountPolicies(), 'statements'));
        $actions = array_merge(...array_column($statements, 'actions'));
        $this->assertContains('account:read', $actions);
        $this->assertContains('account:update', $actions);
        $updateStatement = $this->statementForAction($statements, 'account:update');
        $this->assertNull($updateStatement['condition']);
        $inviteStatement = $this->statementForAction($statements, 'account:member:invite');
        $this->assertSame([
            'clauses' => [
                [
                    'field' => 'resource:accountType',
                    'operator' => 'eq',
                    'value' => 'corporation',
                ],
            ],
        ], $inviteStatement['condition']);
        $this->assertSame([
            'accountIdentifier' => (string) $accountIdentifier,
            'name' => 'Test Account',
        ], $readModel->originalAccount()?->toArray());
        $this->assertNull($readModel->delegationIdentifier());
        $this->assertSame([], $readModel->switchableAccounts());
    }

    #[Group('useDb')]
    public function testProcessReturnsNullAccountIdentifierWhenIdentityDoesNotBelongToAccount(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5fe');
        CreateIdentity::create($identityIdentifier, [
            'identity_name' => 'test-user',
            'email' => 'test@example.com',
            'language' => 'ja',
            'profile_image' => null,
        ]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('set')->never();

        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);
        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame('019de7f3-78f3-7b55-9ed5-17f63e14d5fe', $readModel->identityIdentifier());
        $this->assertSame('test-user', $readModel->identityName());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame('ja', $readModel->language());
        $this->assertNull($readModel->profileImage());
        $this->assertNull($readModel->accountIdentifier());
        $this->assertNull($readModel->accountPrincipalIdentifier());
        $this->assertNull($readModel->accountType());
        $this->assertSame([], $readModel->accountPolicies());
        $this->assertNull($readModel->originalAccount());
        $this->assertNull($readModel->delegationIdentifier());
        $this->assertSame([], $readModel->switchableAccounts());
    }

    #[Group('useDb')]
    public function testProcessReturnsOnlyApprovedSwitchableAccounts(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e001');
        $originalAccountIdentifier = new AccountIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e002');
        $originalPrincipalIdentifier = '019de7f3-78f3-7b55-9ed5-17f63e14e003';
        $this->app->make(AccountAuthorizationSeeder::class)->run();
        CreateIdentity::create($identityIdentifier);
        CreateAccount::create((string) $originalAccountIdentifier, ['name' => 'Original Account']);
        DB::table('account_principals')->insert([
            'id' => $originalPrincipalIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'account_id' => (string) $originalAccountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $statuses = ['approved', 'pending', 'rejected'];
        foreach ($statuses as $index => $status) {
            $suffix = (string) ($index + 4);
            $targetAccountIdentifier = "019de7f3-78f3-7b55-9ed5-17f63e14e00{$suffix}";
            CreateAccount::create($targetAccountIdentifier, ['name' => ucfirst($status) . ' Account']);
            DB::table('account_delegations')->insert([
                'id' => "019de7f3-78f3-7b55-9ed5-17f63e14e01{$suffix}",
                'affiliation_id' => "019de7f3-78f3-7b55-9ed5-17f63e14e02{$suffix}",
                'delegate_account_id' => (string) $originalAccountIdentifier,
                'delegator_account_id' => $targetAccountIdentifier,
                'requested_by_account_id' => (string) $originalAccountIdentifier,
                'status' => $status,
                'direction' => 'from_agency',
                'requested_at' => now(),
                'approved_at' => $status === 'approved' ? now() : null,
                'rejected_at' => $status === 'rejected' ? now() : null,
            ]);
        }

        Redis::shouldReceive('get')->twice()->andReturn(null);
        Redis::shouldReceive('set')->twice();

        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);
        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));
        $this->assertSame([], $readModel->switchableAccounts());

        $this->grantSwitchPermission(
            $originalAccountIdentifier,
            $originalPrincipalIdentifier,
            new PrincipalGroupIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e008'),
            '019de7f3-78f3-7b55-9ed5-17f63e14e009',
        );

        $readModel = $useCase->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame([
            [
                'delegationIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14e014',
                'accountIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14e004',
                'account' => [
                    'accountIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14e004',
                    'name' => 'Approved Account',
                ],
                'isCurrent' => false,
            ],
        ], array_map(static fn ($account): array => $account->toArray(), $readModel->switchableAccounts()));
    }

    #[Group('useDb')]
    public function testProcessReturnsDelegatedCurrentAndOriginalAccountContext(): void
    {
        $identityIdentifier = new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e101');
        $originalAccountIdentifier = new AccountIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e102');
        $effectiveAccountIdentifier = new AccountIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e103');
        $originalPrincipalIdentifier = '019de7f3-78f3-7b55-9ed5-17f63e14e104';
        $effectivePrincipalIdentifier = '019de7f3-78f3-7b55-9ed5-17f63e14e105';
        $delegationIdentifier = '019de7f3-78f3-7b55-9ed5-17f63e14e106';
        $this->app->make(AccountAuthorizationSeeder::class)->run();
        CreateIdentity::create($identityIdentifier);
        CreateAccount::create((string) $originalAccountIdentifier, ['name' => 'Original Account']);
        CreateAccount::create((string) $effectiveAccountIdentifier, ['name' => 'Effective Account']);
        DB::table('account_principals')->insert([
            [
                'id' => $originalPrincipalIdentifier,
                'identity_id' => (string) $identityIdentifier,
                'account_id' => (string) $originalAccountIdentifier,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => $effectivePrincipalIdentifier,
                'identity_id' => (string) $identityIdentifier,
                'account_id' => (string) $effectiveAccountIdentifier,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->grantSwitchPermission(
            $originalAccountIdentifier,
            $originalPrincipalIdentifier,
            new PrincipalGroupIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14e108'),
            '019de7f3-78f3-7b55-9ed5-17f63e14e109',
        );
        DB::table('account_delegations')->insert([
            'id' => $delegationIdentifier,
            'affiliation_id' => '019de7f3-78f3-7b55-9ed5-17f63e14e107',
            'delegate_account_id' => (string) $originalAccountIdentifier,
            'delegator_account_id' => (string) $effectiveAccountIdentifier,
            'requested_by_account_id' => (string) $originalAccountIdentifier,
            'status' => 'approved',
            'direction' => 'from_agency',
            'requested_at' => now(),
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        Redis::shouldReceive('get')->once()->andReturn(json_encode([
            'originalIdentityIdentifier' => (string) $identityIdentifier,
            'originalAccountIdentifier' => (string) $originalAccountIdentifier,
            'originalPrincipalIdentifier' => $originalPrincipalIdentifier,
            'effectiveAccountIdentifier' => (string) $effectiveAccountIdentifier,
            'effectivePrincipalIdentifier' => $effectivePrincipalIdentifier,
            'delegationIdentifier' => $delegationIdentifier,
        ], JSON_THROW_ON_ERROR));
        Redis::shouldReceive('set')->never();

        $readModel = $this->app->make(GetAuthenticatedIdentityInterface::class)
            ->process(new GetAuthenticatedIdentityInput($identityIdentifier));

        $this->assertSame((string) $effectiveAccountIdentifier, $readModel->accountIdentifier());
        $this->assertSame([
            'accountIdentifier' => (string) $originalAccountIdentifier,
            'name' => 'Original Account',
        ], $readModel->originalAccount()?->toArray());
        $this->assertSame($delegationIdentifier, $readModel->delegationIdentifier());
        $this->assertTrue($readModel->switchableAccounts()[0]->toArray()['isCurrent']);
    }

    #[Group('useDb')]
    public function testProcessThrowsWhenIdentityDoesNotExist(): void
    {
        $useCase = $this->app->make(GetAuthenticatedIdentityInterface::class);

        $this->expectException(IdentityNotFoundException::class);

        $useCase->process(new GetAuthenticatedIdentityInput(
            new IdentityIdentifier('019de7f3-78f3-7b55-9ed5-17f63e14d5ff'),
        ));
    }

    private function grantSwitchPermission(
        AccountIdentifier $accountIdentifier,
        string $principalIdentifier,
        PrincipalGroupIdentifier $groupIdentifier,
        string $membershipIdentifier,
    ): void {
        $switcherRoleId = DB::table('account_roles')->where('name', 'DelegationAccountSwitcher')->value('id');
        $this->assertIsString($switcherRoleId);
        CreateAccountPrincipalGroup::create($groupIdentifier, $accountIdentifier, [
            'role_ids' => [$switcherRoleId],
        ]);
        DB::table('account_principal_group_memberships')->insert([
            'id' => $membershipIdentifier,
            'principal_group_id' => (string) $groupIdentifier,
            'principal_id' => $principalIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $statements
     * @return array<string, mixed>
     */
    private function statementForAction(array $statements, string $action): array
    {
        foreach ($statements as $statement) {
            if (in_array($action, $statement['actions'], true)) {
                return $statement;
            }
        }

        $this->fail("Statement for action {$action} was not found.");
    }
}
