<?php

declare(strict_types=1);

namespace Tests\Monetization\Principal\Application\UseCase\Command\CreatePrincipal;

use Database\Seeders\MonetizationAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Monetization\Principal\Application\Exception\AccountMembershipRequiredException;
use Source\Monetization\Principal\Application\Exception\SystemRoleNotFoundException;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInput;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalInterface;
use Source\Monetization\Principal\Application\UseCase\Command\CreatePrincipal\CreatePrincipalOutput;
use Source\Monetization\Principal\Domain\Exception\PrincipalAlreadyExistsException;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

#[Group('useDb')]
class CreatePrincipalTest extends TestCase
{
    public function testOwnerAlsoReceivesOwnerGroup(): void
    {
        [$identity, $account, $groupId] = $this->member();
        $roleId = StrTestHelper::generateUuid();
        DB::table('account_roles')->insert(['id' => $roleId, 'name' => 'Owner', 'account_id' => null]);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $groupId, 'role_id' => $roleId]);
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $output = new CreatePrincipalOutput();
        $this->app()->make(CreatePrincipalInterface::class)->process(new CreatePrincipalInput($identity, $account), $output);
        self::assertSame(['Default', 'Owners'], DB::table('monetization_principal_groups')->orderBy('name')->pluck('name')->all());
        self::assertSame(2, DB::table('monetization_principal_group_memberships')->count());
    }

    public function testCreatesGeneralMembershipForCurrentAccount(): void
    {
        [$identity, $account] = $this->member();
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $output = new CreatePrincipalOutput();
        $this->app()->make(CreatePrincipalInterface::class)->process(new CreatePrincipalInput($identity, $account), $output);
        self::assertSame((string) $identity, $output->toArray()['identityIdentifier']);
        self::assertSame('Default', DB::table('monetization_principal_groups')->value('name'));
        self::assertSame(1, DB::table('monetization_principal_group_memberships')->count());
        self::assertSame('General', DB::table('monetization_roles')->whereIn('id', DB::table('monetization_principal_group_role_attachments')->select('role_id'))->value('name'));
    }

    public function testDuplicateDoesNotRestoreStatusOrMembership(): void
    {
        [$identity, $account] = $this->member();
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        $useCase = $this->app()->make(CreatePrincipalInterface::class);
        $input = new CreatePrincipalInput($identity, $account);
        $useCase->process($input, new CreatePrincipalOutput());
        DB::table('monetization_principals')->update(['status' => 'forced_stop']);
        DB::table('monetization_principal_group_memberships')->delete();

        try {
            $useCase->process($input, new CreatePrincipalOutput());
            self::fail('Duplicate enrollment must not restore privileges.');
        } catch (PrincipalAlreadyExistsException) {
            self::assertSame('forced_stop', DB::table('monetization_principals')->value('status'));
            self::assertSame(0, DB::table('monetization_principal_group_memberships')->count());
        }
    }

    public function testRejectsIdentityWithoutMembershipBeforeWriting(): void
    {
        [$identity, $account] = $this->member();
        $other = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($other, ['email' => 'other@example.com']);

        try {
            $this->app()->make(CreatePrincipalInterface::class)->process(new CreatePrincipalInput($other, $account), new CreatePrincipalOutput());
            self::fail('An unrelated identity must not enroll.');
        } catch (AccountMembershipRequiredException) {
            self::assertSame(0, DB::table('monetization_principals')->count());
            self::assertSame(0, DB::table('monetization_principal_groups')->count());
        }
    }

    public function testLateInitializationFailureRollsBackPrincipalAndInitialGroupTogether(): void
    {
        [$identity, $account, $groupId] = $this->member();
        $roleId = StrTestHelper::generateUuid();
        DB::table('account_roles')->insert(['id' => $roleId, 'name' => 'Owner', 'account_id' => null]);
        DB::table('account_principal_group_role_attachments')->insert(['principal_group_id' => $groupId, 'role_id' => $roleId]);
        $this->app()->make(MonetizationAuthorizationSeeder::class)->run();
        DB::table('monetization_role_policy_attachments')->where('role_id', '09290000-0000-7000-8000-000000000003')->delete();
        DB::table('monetization_roles')->where('name', 'Owner')->delete();

        try {
            DB::transaction(function () use ($identity, $account): void {
                try {
                    $this->app()->make(CreatePrincipalInterface::class)->process(new CreatePrincipalInput($identity, $account), new CreatePrincipalOutput());
                    self::fail('Missing Owner role must fail enrollment.');
                } catch (SystemRoleNotFoundException $exception) {
                    self::assertSame(1, DB::table('monetization_principals')->count());
                    self::assertSame(1, DB::table('monetization_principal_group_memberships')->count());

                    throw $exception;
                }
            });
        } catch (SystemRoleNotFoundException) {
            self::assertSame(0, DB::table('monetization_principals')->count());
            self::assertSame(0, DB::table('monetization_principal_groups')->count());
            self::assertSame(0, DB::table('monetization_principal_group_memberships')->count());
        }
    }

    /** @return array{IdentityIdentifier, AccountIdentifier, string} */
    private function member(): array
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

        return [$identity, $account, $groupId];
    }
}
