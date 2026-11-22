<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Infrastructure\Service;

use Database\Seeders\MonetizationAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Application\Service\ReachablePrincipalGroupManagerInterface;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class ReachablePrincipalGroupManagerTest extends TestCase
{
    public function testUnenrolledAccountIsNotBlockedAndEnrolledManagerMustBeReachableActiveAndAllowed(): void
    {
        $account = new AccountIdentifier(StrTestHelper::generateUuid());
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $account);
        CreateIdentity::create($identity);
        $subject = $this->app()->make(ReachablePrincipalGroupManagerInterface::class);
        self::assertTrue($subject->isPreserved($account, []));
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $id = StrTestHelper::generateUuid();
        $group = StrTestHelper::generateUuid();
        DB::table('monetization_principals')->insert(['id' => $id, 'identity_id' => (string) $identity, 'account_id' => (string) $account, 'status' => 'active']);
        DB::table('monetization_principal_groups')->insert(['id' => $group, 'name' => 'Managers', 'account_id' => (string) $account, 'is_default' => false]);
        DB::table('monetization_principal_group_memberships')->insert(['principal_group_id' => $group, 'principal_id' => $id]);
        DB::table('monetization_principal_group_role_attachments')->insert(['principal_group_id' => $group, 'role_id' => '09290000-0000-7000-8000-000000000003']);
        self::assertFalse($subject->isPreserved($account, []));
        self::assertTrue($subject->isPreserved($account, [$identity]));
        DB::table('monetization_principals')->where('id', $id)->update(['status' => 'suspended']);
        self::assertFalse($subject->isPreserved($account, [$identity]));
        DB::table('monetization_principals')->where('id', $id)->update(['status' => 'active']);
        DB::table('monetization_role_policy_attachments')->delete();
        self::assertFalse($subject->isPreserved($account, [$identity]));
    }
}
