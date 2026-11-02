<?php

declare(strict_types=1);

namespace Tests\Analyze\Principal\Infrastructure\Service;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Analyze\Principal\Infrastructure\Service\AccountMembershipService;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class AccountMembershipServiceTest extends TestCase
{
    public function testRequiresActivePrincipalAndActualSameAccountGroupMembership(): void
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity);
        CreateAccount::create((string) $account);
        $service = new AccountMembershipService();
        self::assertFalse($service->belongsToActiveAccount($identity, $account));
        $id = StrTestHelper::generateUuid();
        $groupId = StrTestHelper::generateUuid();
        DB::table('account_principals')->insert(['id' => $id, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        self::assertFalse($service->belongsToActiveAccount($identity, $account));
        DB::table('account_principal_groups')->insert(['id' => $groupId, 'account_id' => (string) $account, 'name' => 'General']);
        DB::table('account_principal_group_memberships')->insert(['id' => StrTestHelper::generateUuid(), 'principal_group_id' => $groupId, 'principal_id' => $id]);
        self::assertTrue($service->belongsToActiveAccount($identity, $account));
        foreach (['suspended', 'forced_stop'] as $status) {
            DB::table('account_principals')->where('id', $id)->update(['status' => $status]);
            self::assertFalse($service->belongsToActiveAccount($identity, $account));
        }
    }
}
