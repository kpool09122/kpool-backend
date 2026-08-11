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
        Redis::shouldReceive('setex')->once();

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
        Redis::shouldReceive('setex')->never();

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
