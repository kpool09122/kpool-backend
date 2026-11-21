<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Query;

use Database\Seeders\MonetizationAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInput;
use Source\Monetization\Principal\Application\UseCase\Query\ListMembers\ListMembersInterface;
use Source\Monetization\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ListMembersTest extends TestCase
{
    public function testReturnsOnlyEffectiveAccountGroupsAndMembersWithoutWrites(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $principal = new PrincipalIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        CreateIdentity::create($identity, ['identityName' => 'alice', 'email' => 'alice@example.com']);
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        DB::table('monetization_principals')->insert(['id' => (string) $principal, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        $group = StrTestHelper::generateUuid();
        DB::table('monetization_principal_groups')->insert(['id' => $group, 'account_id' => (string) $account, 'name' => 'Owners', 'description' => 'Owners group', 'is_default' => false]);
        DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => $group, 'principal_id' => (string) $principal]);
        DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => '09290000-0000-7000-8000-000000000003']);
        $query = $this->app()->make(ListMembersInterface::class);
        $result = $query->process(new ListMembersInput($account, $principal));
        self::assertCount(1, $result);
        self::assertSame([
            'status' => 'active',
            'principalIdentifier' => (string) $principal,
            'identityIdentifier' => (string) $identity,
            'identityName' => 'alice',
            'email' => 'alice@example.com',
        ], $result[0]->toArray());
        self::assertSame(1, DB::table('monetization_principals')->count());
        self::assertSame(1, DB::table('monetization_principal_groups')->count());
    }
}
